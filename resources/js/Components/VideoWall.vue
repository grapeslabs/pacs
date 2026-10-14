<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import WallCell from './WallCell.vue';

const props = defineProps({
    walls: { type: Array, default: () => [] },
    streams: { type: Array, default: () => [] },
    state: { type: Object, default: () => ({}) },
});

const MAX_SCREENS = props.state.max_screens || 36;
const LAYOUT_COLS = { '2x2': 2, '3x3': 3, '4x4': 4 };
const FOCUS_MAX = 9;
const MIN_WEIGHT = 0.3;
const MAX_WEIGHT = 12;
const LAYOUT_OPTIONS = [
    { key: 'auto', label: 'Авто' },
    { key: 'focus', label: 'Фокус' },
    { key: '2x2', label: '2×2' },
    { key: '3x3', label: '3×3' },
    { key: '4x4', label: '4×4' },
];

const clampN = (value, min, max) => Math.max(min, Math.min(max, value));
const weight = (value) => Math.round(clampN(Number(value) || 1, MIN_WEIGHT, MAX_WEIGHT) * 10000) / 10000;
const clone = (value) => JSON.parse(JSON.stringify(value));
const flatIds = (columns) => (columns || []).flatMap((column) => column.items.map((item) => item.id));

const buildCols = (ids, layout) => {
    if (!ids.length) return [];

    if (layout === 'focus') {
        if (ids.length === 1) return [{ w: 1, items: [{ id: ids[0], h: 1 }] }];

        return [
            { w: 2, items: [{ id: ids[0], h: 1 }] },
            { w: 1, items: ids.slice(1).map((id) => ({ id, h: 1 })) },
        ];
    }

    const columnCount = Math.min(
        ids.length,
        LAYOUT_COLS[layout] || Math.max(1, Math.ceil(Math.sqrt(ids.length)))
    );
    const base = Math.floor(ids.length / columnCount);
    const extra = ids.length % columnCount;
    const columns = [];
    let offset = 0;

    for (let c = 0; c < columnCount; c++) {
        const size = base + (c < extra ? 1 : 0);

        columns.push({ w: 1, items: ids.slice(offset, offset + size).map((id) => ({ id, h: 1 })) });
        offset += size;
    }

    return columns;
};

const profiles = ref(clone(props.walls));
const storedId = Number(localStorage.getItem('pacs_wall_active'));
const initial = profiles.value.find((profile) => profile.id === storedId) || profiles.value[0] || null;

const activeId = ref(initial?.id ?? null);
const cols = ref(clone(initial?.layout ?? []));
const editing = ref(false);
const isFullscreen = ref(false);
const fsBar = ref(true);
const audioId = ref(null);
const pausedIds = ref([]);
const profileMenu = ref(false);
const camQuery = ref('');
const renameId = ref(null);
const renameValue = ref('');
const deleteTarget = ref(null);
const saveTarget = ref(null);
const confirmExit = ref(false);
const pendingAction = ref(null);
const saving = ref(false);

const dragId = ref(null);
const dragFromPanel = ref(false);
const overCell = ref(null);
const insAt = ref(null);
const colIns = ref(null);
const dropOk = ref(false);

const wallRef = ref(null);
const gridRef = ref(null);

let fsTimer = null;

const streamById = computed(() => {
    const map = {};
    props.streams.forEach((stream) => { map[stream.id] = stream; });

    return map;
});

const sanitize = (layout) => (layout || [])
    .map((column) => ({
        w: Number(column.w) || 1,
        items: (column.items || [])
            .filter((item) => streamById.value[item.id])
            .map((item) => ({ id: Number(item.id), h: Number(item.h) || 1 })),
    }))
    .filter((column) => column.items.length);

const signature = (layout) => JSON.stringify(sanitize(layout).map((column) => ({
    w: Math.round(column.w * 10000) / 10000,
    items: column.items.map((item) => ({ id: item.id, h: Math.round(item.h * 10000) / 10000 })),
})));

const active = computed(() => profiles.value.find((profile) => profile.id === activeId.value) || null);
const ids = computed(() => flatIds(cols.value));
const count = computed(() => ids.value.length);
const totalW = computed(() => cols.value.reduce((sum, column) => sum + column.w, 0) || 1);
const dirty = computed(() => signature(active.value?.layout ?? []) !== signature(cols.value));

const subtitle = computed(() => {
    if (editing.value) return 'Редактирование · перетащите камеру в сетку, тяните угол для размера';

    const name = active.value?.name || 'Без профиля';

    return `${name} · ${count.value} из ${props.streams.length} камер · многопоточный вывод`;
});

const panelStreams = computed(() => {
    const query = camQuery.value.trim().toLowerCase();

    return props.streams
        .filter((stream) => !query || `${stream.name} ${stream.uid} ${stream.location || ''}`.toLowerCase().includes(query))
        .slice()
        .sort((a, b) => (ids.value.includes(b.id) ? 1 : 0) - (ids.value.includes(a.id) ? 1 : 0));
});

const camWord = (value) => {
    const tail = Math.abs(value) % 100;
    const last = tail % 10;

    if (tail > 10 && tail < 20) return 'камер';
    if (last === 1) return 'камера';
    if (last > 1 && last < 5) return 'камеры';

    return 'камер';
};

const cams = (value) => `${value} ${camWord(value)}`;

const columnHeight = (column) => column.items.reduce((sum, item) => sum + item.h, 0) || 1;
const cellIndex = (id) => ids.value.indexOf(id) + 1;
const profileCount = (profile) => flatIds(profile.layout || []).length;

const toast = (message, type = 'success') => window.MoonShine?.ui?.toast?.(message, type);

const request = (url, method, data = {}) => axios({
    url,
    method,
    data,
    headers: { 'X-CSRF-TOKEN': props.state.csrf },
});

const requestError = (e) => {
    const status = e.response?.status;

    if (status === 419) return 'Сессия истекла — откройте страницу заново и повторите';
    if (status === 404) return 'Профиль не найден — возможно, его уже удалили';
    if (status === 403) return 'Недостаточно прав для изменения видеостен';

    if (status === 422) {
        const errors = e.response?.data?.errors ?? {};

        return errors.layout?.[0]
            || (errors.name ? 'Укажите название профиля, не длиннее 255 символов' : 'Некорректные данные');
    }

    return 'Не удалось сохранить видеостену';
};

const doLoad = (profile) => {
    activeId.value = profile?.id ?? null;
    cols.value = sanitize(profile?.layout ?? []);
    renameId.value = null;
    profileMenu.value = false;
    audioId.value = null;
    pausedIds.value = [];
};

const guardDirty = (run) => {
    if (editing.value && dirty.value) {
        pendingAction.value = run;
        return;
    }

    run();
};

const loadProfile = (profile) => {
    if (profile.id === activeId.value) {
        doLoad(profile);
        return;
    }

    guardDirty(() => doLoad(profile));
};

const saveToProfile = async (profile) => {
    if (!profile) {
        throw new Error('Профиль не выбран');
    }

    saving.value = true;

    try {
        const { data } = await request(profile.update_url, 'post', { layout: cols.value });

        profile.layout = data.layout;
        cols.value = clone(data.layout);
        toast(`Сетка сохранена · ${profile.name}`);
    } catch (e) {
        toast(requestError(e), 'error');
        throw e;
    } finally {
        saving.value = false;
    }
};

const requestSaveTo = (profile) => {
    if (profile.id === activeId.value) {
        saveToProfile(profile).catch(() => {});
        return;
    }

    saveTarget.value = profile;
};

const confirmSaveTo = () => {
    const profile = saveTarget.value;

    if (!profile) return;

    saveToProfile(profile)
        .then(() => { saveTarget.value = null; })
        .catch(() => {});
};

const createProfile = async (layout = []) => {
    saving.value = true;

    try {
        const { data } = await request(props.state.store_url, 'post', { name: 'Новый профиль', layout });

        profiles.value.push(data);
        doLoad(data);
        renameId.value = data.id;
        renameValue.value = data.name;

        return data;
    } catch (e) {
        toast(requestError(e), 'error');
        throw e;
    } finally {
        saving.value = false;
    }
};

const saveCurrent = () => (active.value ? saveToProfile(active.value) : createProfile(cols.value));

const addProfile = () => {
    if (editing.value && dirty.value && !active.value) {
        createProfile(cols.value).catch(() => {});
        return;
    }

    guardDirty(() => createProfile().catch(() => {}));
};

const commitName = async (profile) => {
    const name = renameValue.value.trim();

    renameId.value = null;

    if (!name || name === profile.name) return;

    try {
        const { data } = await request(profile.update_url, 'post', { name });

        profile.name = data.name;
    } catch (e) {
        toast(requestError(e), 'error');
    }
};

const deleteProfile = async (profile) => {
    try {
        await request(profile.destroy_url, 'delete');

        profiles.value = profiles.value.filter((item) => item.id !== profile.id);

        if (activeId.value === profile.id) doLoad(profiles.value[0] || null);

        toast(`Профиль удалён · ${profile.name}`);
    } catch (e) {
        toast(requestError(e), 'error');
    }
};

const setPreset = (key) => {
    cols.value = buildCols(ids.value, key);
};

const presetDisabled = (key) => {
    const capacity = key === 'focus' ? FOCUS_MAX : (LAYOUT_COLS[key] ? LAYOUT_COLS[key] ** 2 : 0);

    return capacity > 0 && count.value > capacity;
};

const removeCam = (id) => {
    cols.value = cols.value
        .map((column) => ({ ...column, items: column.items.filter((item) => item.id !== id) }))
        .filter((column) => column.items.length);
};

const spotlight = (id) => {
    const rest = cols.value
        .map((column) => ({ ...column, items: column.items.filter((item) => item.id !== id) }))
        .filter((column) => column.items.length)
        .map((column) => ({ ...column, w: 1 }));

    cols.value = [{ w: 2, items: [{ id, h: 1 }] }, ...rest];
};

const toggleWide = (ci) => {
    if (cols.value.length < 2) return;

    const maxOther = Math.max(1, ...cols.value.filter((_, index) => index !== ci).map((column) => column.w));
    const isBig = cols.value[ci].w > maxOther + 0.5;
    const wide = Math.min(MAX_WEIGHT, (cols.value.length - 1) * 2 + 1);

    cols.value = cols.value.map((column, index) => ({ ...column, w: index === ci ? (isBig ? 1 : wide) : 1 }));
};

const overLimit = () => {
    toast(`На стену помещается до ${MAX_SCREENS} камер`, 'error');
};

const resetDragState = () => {
    dragId.value = null;
    dragFromPanel.value = false;
    overCell.value = null;
    insAt.value = null;
    colIns.value = null;
    dropOk.value = false;
};

const readDragId = (event) => {
    const raw = event.dataTransfer.getData('text/plain');

    if (!raw || (raw[0] !== 'C' && raw[0] !== 'L')) return null;

    const id = Number(raw.slice(1));

    return Number.isInteger(id) && id > 0 ? id : null;
};

const onCellDragStart = (event, id) => {
    event.dataTransfer.setData('text/plain', `C${id}`);
    event.dataTransfer.effectAllowed = 'move';
    dragId.value = id;
    dragFromPanel.value = false;
};

const onPanelDragStart = (event, id) => {
    event.dataTransfer.setData('text/plain', `L${id}`);
    event.dataTransfer.effectAllowed = 'copy';
    dragId.value = id;
    dragFromPanel.value = true;
};

const onGridDragOver = (event) => {
    if (!editing.value) return;

    event.preventDefault();

    if (!count.value) {
        dropOk.value = true;
        return;
    }

    const columns = gridRef.value ? Array.from(gridRef.value.querySelectorAll('.wall-col')) : [];
    let index = columns.length;

    for (let i = 0; i < columns.length; i++) {
        const rect = columns[i].getBoundingClientRect();

        if (event.clientX < rect.left + rect.width / 2) {
            index = i;
            break;
        }
    }

    colIns.value = index;
    insAt.value = null;
    overCell.value = null;
};

const onCellDragOver = (event, ci, ii, id) => {
    event.preventDefault();
    event.stopPropagation();
    colIns.value = null;

    if (!dragFromPanel.value) {
        if (dragId.value !== id) {
            overCell.value = `${ci}-${ii}`;
            insAt.value = null;
        }

        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const after = event.clientY - rect.top > rect.height / 2;

    insAt.value = `${ci}-${after ? ii + 1 : ii}`;
    overCell.value = null;
};

const dropOnCell = (event, ci, ii) => {
    const id = readDragId(event);

    if (id === null) return;

    const target = cols.value[ci]?.items[ii];

    if (!target || target.id === id) return;

    const next = cols.value.map((column) => ({ ...column, items: column.items.map((item) => ({ ...item })) }));
    let sourceCi = -1;
    let sourceIi = -1;

    next.forEach((column, x) => column.items.forEach((item, y) => {
        if (item.id === id) {
            sourceCi = x;
            sourceIi = y;
        }
    }));

    if (sourceCi >= 0) {
        const targetId = next[ci].items[ii].id;

        next[ci].items[ii].id = id;
        next[sourceCi].items[sourceIi].id = targetId;
        cols.value = next;

        return;
    }

    if (count.value >= MAX_SCREENS) {
        overLimit();
        return;
    }

    const displaced = next[ci].items[ii].id;

    next[ci].items[ii].id = id;

    let shortest = 0;

    for (let i = 1; i < next.length; i++) {
        if (next[i].items.length < next[shortest].items.length) shortest = i;
    }

    next[shortest].items.push({ id: displaced, h: 1 });
    cols.value = next;
};

const dropInsert = (event, at) => {
    const id = readDragId(event);

    if (id === null || ids.value.includes(id)) return;

    if (count.value >= MAX_SCREENS) {
        overLimit();
        return;
    }

    const next = cols.value.map((column) => ({ ...column, items: column.items.map((item) => ({ ...item })) }));
    const [ci, ii] = String(at).split('-').map(Number);

    if (!next[ci]) next.push({ w: 1, items: [{ id, h: 1 }] });
    else next[ci].items.splice(clampN(ii, 0, next[ci].items.length), 0, { id, h: 1 });

    cols.value = next;
};

const onCellDrop = (event, ci, ii) => {
    event.preventDefault();
    event.stopPropagation();

    const raw = event.dataTransfer.getData('text/plain');

    if (raw && raw[0] === 'L' && insAt.value !== null) dropInsert(event, insAt.value);
    else dropOnCell(event, ci, ii);

    resetDragState();
};

const dropOnWall = (event) => {
    if (!editing.value) return;

    event.preventDefault();

    const at = colIns.value;
    const id = readDragId(event);

    if (id === null) {
        resetDragState();
        return;
    }

    if (!ids.value.includes(id) && count.value >= MAX_SCREENS) {
        overLimit();
        resetDragState();

        return;
    }

    const next = cols.value.map((column) => ({
        ...column,
        items: column.items.filter((item) => item.id !== id).map((item) => ({ ...item })),
    }));
    const index = at === null ? next.length : clampN(at, 0, next.length);

    next.splice(index, 0, { w: 1, items: [{ id, h: 1 }] });
    cols.value = next.filter((column) => column.items.length);

    resetDragState();
};

const startResize = (event, ci, ii) => {
    event.preventDefault();
    event.stopPropagation();

    const grid = gridRef.value;

    if (!grid) return;

    const rect = grid.getBoundingClientRect();
    const columnEls = Array.from(grid.querySelectorAll('.wall-col'));
    const cellEls = columnEls[ci]
        ? Array.from(columnEls[ci].children).filter((el) => el.classList.contains('edit-cell'))
        : [];

    const contentW = columnEls.reduce((sum, el) => sum + el.getBoundingClientRect().width, 0) || rect.width;
    const contentH = cellEls.reduce((sum, el) => sum + el.getBoundingClientRect().height, 0) || rect.height;

    const startX = event.clientX;
    const startY = event.clientY;
    const startColW = cols.value.map((column) => column.w);
    const startItemH = cols.value[ci].items.map((item) => item.h);
    const sumW = startColW.reduce((a, b) => a + b, 0);
    const sumH = startItemH.reduce((a, b) => a + b, 0);

    dragId.value = cols.value[ci].items[ii].id;

    const move = (moveEvent) => {
        const dw = ((moveEvent.clientX - startX) / contentW) * sumW;
        const dh = ((moveEvent.clientY - startY) / contentH) * sumH;

        const maxW = Math.min(MAX_WEIGHT, sumW - MIN_WEIGHT * (startColW.length - 1));
        const targetW = clampN(startColW[ci] + dw, MIN_WEIGHT, Math.max(MIN_WEIGHT, maxW));
        const restW = sumW - startColW[ci];
        const scaleW = restW > 0 ? (sumW - targetW) / restW : 1;

        const maxH = Math.min(MAX_WEIGHT, sumH - MIN_WEIGHT * (startItemH.length - 1));
        const targetH = clampN(startItemH[ii] + dh, MIN_WEIGHT, Math.max(MIN_WEIGHT, maxH));
        const restH = sumH - startItemH[ii];
        const scaleH = restH > 0 ? (sumH - targetH) / restH : 1;

        cols.value = cols.value.map((column, index) => {
            const w = weight(index === ci ? targetW : startColW[index] * scaleW);

            if (index !== ci) return { ...column, w };

            return {
                ...column,
                w,
                items: column.items.map((item, j) => ({
                    ...item,
                    h: weight(j === ii ? targetH : startItemH[j] * scaleH),
                })),
            };
        });
    };

    const up = () => {
        dragId.value = null;
        window.removeEventListener('mousemove', move);
        window.removeEventListener('mouseup', up);
    };

    window.addEventListener('mousemove', move);
    window.addEventListener('mouseup', up);
};

const toggleAudio = (id) => {
    audioId.value = audioId.value === id ? null : id;
};

const togglePause = (id) => {
    pausedIds.value = pausedIds.value.includes(id)
        ? pausedIds.value.filter((item) => item !== id)
        : [...pausedIds.value, id];
};

const openCam = (stream) => {
    if (isFullscreen.value) return;

    window.location.href = stream.player_url;
};

const startEditing = () => {
    editing.value = true;
    audioId.value = null;
    pausedIds.value = [];
};

const finishEditing = () => {
    if (!dirty.value) {
        editing.value = false;
        return;
    }

    confirmExit.value = true;
};

const runPendingAction = () => {
    const run = pendingAction.value;

    pendingAction.value = null;
    run?.();
};

const revealFsBar = () => {
    fsBar.value = true;
    clearTimeout(fsTimer);
    fsTimer = setTimeout(() => { fsBar.value = false; }, 2600);
};

const onMouseMoveFs = (event) => {
    if (event.clientY < 90) revealFsBar();
};

const onFullscreenChange = () => {
    isFullscreen.value = document.fullscreenElement === wallRef.value;

    if (isFullscreen.value) {
        revealFsBar();
        window.addEventListener('mousemove', onMouseMoveFs);
    } else {
        clearTimeout(fsTimer);
        fsBar.value = true;
        window.removeEventListener('mousemove', onMouseMoveFs);
    }
};

const enterFullscreen = () => {
    wallRef.value?.requestFullscreen?.().catch(() => {
        toast('Полноэкранный режим недоступен', 'error');
    });
};

const exitFullscreen = () => {
    if (document.fullscreenElement) document.exitFullscreen();
};

const onBeforeUnload = (event) => {
    if (!editing.value || !dirty.value) return;

    event.preventDefault();
    event.returnValue = '';
};

watch(activeId, (value) => {
    if (value !== null) localStorage.setItem('pacs_wall_active', String(value));
});

watch(renameId, (value) => {
    if (value === null) return;

    nextTick(() => {
        const input = document.querySelector('.wprof input');

        input?.focus();
        input?.select();
    });
});

onMounted(() => {
    document.addEventListener('fullscreenchange', onFullscreenChange);
    window.addEventListener('beforeunload', onBeforeUnload);
});

onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    window.removeEventListener('beforeunload', onBeforeUnload);
    window.removeEventListener('mousemove', onMouseMoveFs);
    clearTimeout(fsTimer);
});
</script>

<template>
    <div class="videowall" @click="profileMenu = false">
        <div class="wall-head">
            <div class="wall-head-title">
                <h2>Видеостены</h2>
                <p>{{ subtitle }}</p>
            </div>

            <div class="wall-head-actions">
                <template v-if="editing">
                    <button type="button" class="btn primary" :disabled="saving" @click="finishEditing">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Завершить редактирование
                    </button>
                </template>
                <template v-else>
                    <div class="prof-select" @click.stop>
                        <button type="button" class="btn" @click="profileMenu = !profileMenu">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18v11H3z"></path><path d="M8 20h8"></path><path d="M12 16v4"></path><path d="M8 9h.01"></path><path d="M12 9h.01"></path><path d="M16 9h.01"></path><path d="M8 12.5h.01"></path><path d="M12 12.5h.01"></path><path d="M16 12.5h.01"></path></svg>                            <span class="prof-name">{{ active?.name || 'Профиль' }}</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>

                        <div v-if="profileMenu" class="prof-pop">
                            <div v-if="!profiles.length" class="prof-pop-empty">Профилей пока нет</div>
                            <div
                                v-for="profile in profiles"
                                :key="profile.id"
                                class="pp-item"
                                :class="{ active: profile.id === activeId }"
                                @click="loadProfile(profile)"
                            >
                                <span class="pp-nm">{{ profile.name }}</span>
                                <span class="pp-cnt">{{ profileCount(profile) }}</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn" :disabled="!count" @click="enterFullscreen">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
                        На весь экран
                    </button>

                    <button type="button" class="btn primary" @click="startEditing">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                        Редактировать сетку
                    </button>
                </template>
            </div>
        </div>

        <div class="wall-shell" :class="{ editing }">
            <div class="wall-main">
                <div v-if="editing" class="wall-toolbar">
                    <span class="wt-label">Раскладка</span>
                    <div class="wt-layouts">
                        <button
                            v-for="option in LAYOUT_OPTIONS"
                            :key="option.key"
                            type="button"
                            class="wt-lay"
                            :disabled="presetDisabled(option.key) || !count"
                            :title="presetDisabled(option.key) ? `Не вмещает ${count} камер` : ''"
                            @click="setPreset(option.key)"
                        >{{ option.label }}</button>
                    </div>
                    <span class="wt-hint">Тяните угол камеры, чтобы изменить размер</span>
                    <span class="wt-count">Камер: <b>{{ count }}</b> / {{ MAX_SCREENS }}</span>
                </div>

                <div
                    ref="wallRef"
                    class="wall-stage"
                    :class="{ 'is-fs': isFullscreen }"
                >
                    <div v-if="isFullscreen" class="wall-fs-bar" :class="{ hidden: !fsBar }">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="8" height="16" rx="1.5"></rect><rect x="13" y="4" width="8" height="7" rx="1.5"></rect><rect x="13" y="13" width="8" height="7" rx="1.5"></rect></svg>
                        <b>{{ active?.name || 'Видеостена' }}</b>
                        <span>· многопоточный · {{ count }} камер</span>
                        <button type="button" class="wall-fs-exit" title="Выйти (Esc)" @click="exitFullscreen">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    <div
                        ref="gridRef"
                        class="wall-grid"
                        :class="{ editing, empty: !count, dropok: dropOk }"
                        @dragover="onGridDragOver"
                        @dragleave="colIns = null; dropOk = false"
                        @drop="dropOnWall"
                    >
                        <div v-if="!count" class="wall-drop-hint">
                            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="8" height="16" rx="1.5"></rect><rect x="13" y="4" width="8" height="7" rx="1.5"></rect><rect x="13" y="13" width="8" height="7" rx="1.5"></rect></svg>
                            <span>{{ editing ? 'Добавьте камеры' : (profiles.length ? 'В профиле нет камер' : 'Профили ещё не созданы') }}</span>
                            <small v-if="editing">Перетащите камеры из списка справа</small>
                        </div>

                        <template v-for="(column, ci) in cols" :key="ci">
                            <div v-if="editing && colIns === ci && insAt === null && overCell === null" class="col-ins-line"></div>

                            <div class="wall-col" :style="{ flexGrow: column.w / totalW, flexBasis: 0 }">
                                <template v-for="(item, ii) in column.items" :key="item.id">
                                    <div v-if="editing && insAt === `${ci}-${ii}`" class="ins-line"></div>

                                    <div
                                        v-if="editing"
                                        class="edit-cell"
                                        :class="{ dragging: dragId === item.id, over: overCell === `${ci}-${ii}` && dragId !== item.id }"
                                        :style="{ flexGrow: item.h / columnHeight(column), flexBasis: 0 }"
                                        draggable="true"
                                        @dragstart="onCellDragStart($event, item.id)"
                                        @dragend="resetDragState"
                                        @dragover="onCellDragOver($event, ci, ii, item.id)"
                                        @dragleave="overCell = overCell === `${ci}-${ii}` ? null : overCell"
                                        @drop="onCellDrop($event, ci, ii)"
                                        @dblclick="toggleWide(ci)"
                                    >
                                        <span class="wc-name"><b>{{ cellIndex(item.id) }}</b>{{ streamById[item.id]?.name }}</span>
                                        <div class="wc-edit-tools">
                                            <button type="button" class="wc-tool" title="Крупнее — вынести в отдельную колонку" @click.stop="spotlight(item.id)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
                                            </button>
                                            <button type="button" class="wc-tool danger" title="Убрать из сетки" @click.stop="removeCam(item.id)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            </button>
                                        </div>
                                        <div class="wc-resize" title="Потяните — размер · двойной клик — на всю ширину" @mousedown="startResize($event, ci, ii)"></div>
                                    </div>

                                    <WallCell
                                        v-else-if="streamById[item.id]"
                                        :stream="streamById[item.id]"
                                        :muted="audioId !== item.id"
                                        :paused="pausedIds.includes(item.id)"
                                        :clickable="!isFullscreen"
                                        :style="{ flexGrow: item.h / columnHeight(column), flexBasis: 0 }"
                                        @toggle-audio="toggleAudio(item.id)"
                                        @toggle-pause="togglePause(item.id)"
                                        @open="openCam(streamById[item.id])"
                                    />

                                    <div v-if="editing && ii === column.items.length - 1 && insAt === `${ci}-${ii + 1}`" class="ins-line"></div>
                                </template>
                            </div>

                            <div v-if="editing && ci === cols.length - 1 && colIns === cols.length && insAt === null && overCell === null" class="col-ins-line"></div>
                        </template>
                    </div>
                </div>
            </div>

            <aside v-if="editing" class="wall-panel" @click.stop>
                <div class="wall-panel-block wpb-profiles">
                    <div class="wpb-head">
                        <h4>Профили</h4>
                        <button type="button" class="wpb-add" title="Добавить профиль" @click="addProfile">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </button>
                    </div>

                    <div class="wprof-list">
                        <div v-if="!profiles.length" class="wcam-empty">Создайте первый профиль</div>

                        <div
                            v-for="profile in profiles"
                            :key="profile.id"
                            class="wprof"
                            :class="{ active: profile.id === activeId }"
                        >
                            <input
                                v-if="renameId === profile.id"
                                v-model="renameValue"
                                placeholder="Имя профиля"
                                @blur="commitName(profile)"
                                @keydown.enter="$event.target.blur()"
                                @keydown.esc="renameId = null"
                            />
                            <template v-else>
                                <span class="wp-nm" :title="profile.name" @click="loadProfile(profile)">{{ profile.name }}</span>
                                <span class="wp-cnt">{{ profileCount(profile) }}</span>
                                <div class="wp-acts">
                                    <button type="button" class="act" title="Сохранить текущую сетку в профиль" @click="requestSaveTo(profile)">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                    </button>
                                    <button type="button" class="act" title="Переименовать" @click="renameId = profile.id; renameValue = profile.name">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                                    </button>
                                    <button type="button" class="act danger" title="Удалить профиль" @click="deleteTarget = profile">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="wall-panel-block wpb-cams">
                    <div class="wpb-head">
                        <h4>Камеры</h4>
                        <span class="wp-cnt accent">{{ count }} / {{ streams.length }}</span>
                    </div>

                    <div class="wcam-search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input v-model="camQuery" placeholder="Поиск камеры…" />
                        <button v-if="camQuery" type="button" class="wcam-search-x" title="Очистить" @click="camQuery = ''">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    <div class="wcam-list">
                        <div v-if="!panelStreams.length" class="wcam-empty">Ничего не найдено</div>

                        <div
                            v-for="stream in panelStreams"
                            :key="stream.id"
                            class="wcam"
                            :class="{ placed: ids.includes(stream.id) }"
                            :draggable="!ids.includes(stream.id)"
                            @dragstart="onPanelDragStart($event, stream.id)"
                            @dragend="resetDragState"
                        >
                            <div class="wc-info">
                                <div class="wc-t">{{ stream.name }}</div>
                                <div class="wc-s">{{ stream.uid }}<template v-if="stream.location"> · {{ stream.location }}</template></div>
                            </div>
                            <button v-if="ids.includes(stream.id)" type="button" class="act danger" title="Убрать со стены" @click="removeCam(stream.id)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div v-if="deleteTarget" class="wall-modal-overlay" @click.self="deleteTarget = null">
            <div class="wall-modal">
                <h3>Удалить профиль?</h3>
                <p>Профиль «{{ deleteTarget.name }}» будет удалён. Сохранённая в нём сетка потеряется.</p>
                <div class="wall-modal-actions">
                    <button type="button" class="btn" @click="deleteTarget = null">Отмена</button>
                    <button type="button" class="btn danger" @click="deleteProfile(deleteTarget); deleteTarget = null">Удалить</button>
                </div>
            </div>
        </div>

        <div v-if="saveTarget" class="wall-modal-overlay" @click.self="saveTarget = null">
            <div class="wall-modal">
                <h3>Сохранить в другой профиль?</h3>
                <p v-if="active">
                    Вы редактируете профиль «{{ active.name }}», а сохраняете сетку в «{{ saveTarget.name }}».
                    Текущая сетка ({{ cams(count) }}) заменит сетку профиля «{{ saveTarget.name }}» ({{ cams(profileCount(saveTarget)) }}).
                </p>
                <p v-else>
                    Текущая сетка ({{ cams(count) }}) заменит сетку профиля «{{ saveTarget.name }}» ({{ cams(profileCount(saveTarget)) }}).
                </p>
                <div class="wall-modal-actions">
                    <button type="button" class="btn" :disabled="saving" @click="saveTarget = null">Отмена</button>
                    <button type="button" class="btn primary" :disabled="saving" @click="confirmSaveTo">Сохранить</button>
                </div>
            </div>
        </div>

        <div v-if="confirmExit" class="wall-modal-overlay" @click.self="confirmExit = false">
            <div class="wall-modal">
                <h3>Сохранить изменения?</h3>
                <p v-if="active">Вы внесли изменения в профиль «{{ active.name }}». Сохранить их перед выходом из редактирования?</p>
                <p v-else>Сетка не сохранена: профиль ещё не создан. Создать новый профиль с этой сеткой?</p>
                <div class="wall-modal-actions">
                    <button type="button" class="btn" @click="confirmExit = false; doLoad(active); editing = false">Не сохранять</button>
                    <button type="button" class="btn primary" :disabled="saving" @click="saveCurrent().then(() => { confirmExit = false; editing = false; }).catch(() => {})">{{ active ? 'Сохранить' : 'Создать профиль' }}</button>
                </div>
            </div>
        </div>

        <div v-if="pendingAction" class="wall-modal-overlay" @click.self="pendingAction = null">
            <div class="wall-modal">
                <h3>Сохранить изменения?</h3>
                <p v-if="active">Вы внесли изменения в профиль «{{ active.name }}». Сохранить их перед тем, как продолжить?</p>
                <p v-else>Сетка не сохранена: профиль ещё не создан. Создать новый профиль с этой сеткой?</p>
                <div class="wall-modal-actions">
                    <button type="button" class="btn" @click="runPendingAction">Не сохранять</button>
                    <button type="button" class="btn primary" :disabled="saving" @click="saveCurrent().then(runPendingAction).catch(() => {})">{{ active ? 'Сохранить' : 'Создать профиль' }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.videowall { font-family: inherit; }

.wall-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.wall-head-title { min-width: 0; }
.wall-head-title h2 { font-size: 22px; font-weight: 700; color: #1a1a2e; margin: 0; line-height: 1.2; }
.wall-head-title p { font-size: 13px; color: #7F7F9D; margin: 4px 0 0; }
.wall-head-actions { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 9999px;
    background: #fff;
    color: #4E4E61;
    border: 1px solid #D7D0EF;
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: border-color .15s, color .15s;
}

.btn:hover:not(:disabled) { border-color: #7381F4; color: #1a1a2e; }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn.primary { background: linear-gradient(180deg, #7E92F8 0%, #6972F0 100%); border-color: transparent; color: #fff; }
.btn.primary:hover:not(:disabled) { color: #fff; }
.btn.danger { background: #F04138; border-color: transparent; color: #fff; transition: background-color .15s, border-color .15s; }
.btn.danger:hover:not(:disabled) { background: #D93A32; border-color: transparent; color: #fff; }

.prof-select { position: relative; }
.prof-name { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.prof-pop {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    z-index: 30;
    min-width: 240px;
    max-height: 320px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #D7DBEF;
    border-radius: 12px;
    box-shadow: 0 20px 80px rgba(127, 127, 157, .18);
    padding: 6px;
}

.prof-pop-empty { padding: 12px; font-size: 13px; font-weight: 600; color: #A6ABC2; text-align: center; }

.pp-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    color: #1a1a2e;
}

.pp-item:hover { background: #F1F3FB; }
.pp-item.active { background: #EFF1FF; }
.pp-nm { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pp-cnt { flex: 0 0 auto; font-size: 11px; font-weight: 700; color: #A6ABC2; }

.wall-shell { display: flex; gap: 18px; align-items: flex-start; }
.wall-main { flex: 1 1 auto; min-width: 0; }

.wall-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
    padding: 11px 14px;
    background: #fff;
    border: 1px solid #D7DBEF;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(127, 127, 157, .08);
}

.wt-label { font-weight: 800; font-size: 12px; color: #6B7390; }
.wt-layouts { display: inline-flex; gap: 6px; flex-wrap: wrap; }

.wt-lay {
    border: 1px solid #D7DBEF;
    background: #F1F3FB;
    color: #6B7390;
    padding: 6px 11px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 12.5px;
    font-family: inherit;
    cursor: pointer;
}

.wt-lay:hover:not(:disabled) { border-color: #C4DCFF; color: #1a1a2e; }
.wt-lay:disabled { opacity: .4; cursor: not-allowed; }
.wt-hint { font-weight: 600; font-size: 12px; color: #A6ABC2; }
.wt-count { margin-left: auto; font-weight: 800; font-size: 12.5px; color: #6B7390; }
.wt-count b { color: #7381F4; }

.wall-stage { position: relative; }
.wall-stage.is-fs { background: #000; display: flex; flex-direction: column; }

.wall-grid {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    gap: 10px;
    background: #1E1E1E;
    border-radius: 16px;
    padding: 10px;
    aspect-ratio: 16 / 9;
    width: 100%;
    box-shadow: 0 8px 24px rgba(127, 127, 157, .10);
}

.wall-stage.is-fs .wall-grid { flex: 1 1 auto; aspect-ratio: auto; height: 100%; border-radius: 0; box-shadow: none; }
.wall-grid.empty { align-items: center; justify-content: center; }
.wall-grid.empty.dropok { outline: 2.5px dashed #7381F4; outline-offset: -4px; }
.wall-col { display: flex; flex-direction: column; align-self: stretch; gap: 10px; min-width: 0; min-height: 0; }

.wall-drop-hint {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: rgba(255, 255, 255, .6);
    font-weight: 700;
    font-size: 15px;
    pointer-events: none;
}

.wall-drop-hint small { font-weight: 600; font-size: 12.5px; color: rgba(255, 255, 255, .4); }

.edit-cell {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background: #212030;
    min-width: 0;
    min-height: 0;
    cursor: grab;
}

.edit-cell::before {
    content: "";
    position: absolute;
    inset: 0;
    border: 1.5px solid rgba(255, 255, 255, .16);
    border-radius: inherit;
    pointer-events: none;
    z-index: 5;
}

.edit-cell:hover::before { border-width: 2px; border-color: #7381F4; }
.edit-cell.dragging { opacity: .45; }
.edit-cell.dragging::before { border: 2px dashed #7381F4; }
.edit-cell.over::before { border-width: 2.5px; border-style: dashed; border-color: #7381F4; }
.edit-cell.over::after { content: ""; position: absolute; inset: 0; z-index: 4; background: rgba(115, 129, 244, .14); pointer-events: none; }

.wc-name {
    position: absolute;
    top: 8px;
    left: 8px;
    display: inline-flex;
    align-items: center;
    background: rgba(0, 0, 0, .58);
    color: #fff;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 11px;
    max-width: calc(100% - 80px);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    z-index: 3;
}

.wc-name b {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    margin-right: 6px;
    border-radius: 4px;
    background: #7381F4;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
}

.wc-edit-tools { position: absolute; top: 6px; right: 6px; display: none; gap: 5px; z-index: 7; }
.edit-cell:hover .wc-edit-tools { display: flex; }

.wc-tool {
    width: 27px;
    height: 27px;
    border: 0;
    border-radius: 7px;
    background: rgba(0, 0, 0, .62);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wc-tool:hover { background: #7381F4; }
.wc-tool.danger:hover { background: #F04138; }

.wc-resize { position: absolute; right: 0; bottom: 0; width: 26px; height: 26px; cursor: nwse-resize; z-index: 8; display: none; }
.edit-cell:hover .wc-resize { display: block; }

.wc-resize::after {
    content: "";
    position: absolute;
    right: 5px;
    bottom: 5px;
    width: 11px;
    height: 11px;
    border-right: 2.5px solid #fff;
    border-bottom: 2.5px solid #fff;
    border-bottom-right-radius: 3px;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .5));
}

.ins-line { flex: 0 0 0; align-self: stretch; height: 0; margin: -5px 0; position: relative; pointer-events: none; }
.ins-line::before { content: ""; position: absolute; left: 2px; right: 2px; top: -1px; border-top: 2.5px dashed #7381F4; }
.col-ins-line { flex: 0 0 0; align-self: stretch; width: 0; margin: 0 -5px; position: relative; pointer-events: none; }
.col-ins-line::before { content: ""; position: absolute; top: 2px; bottom: 2px; left: -1px; border-left: 2.5px dashed #7381F4; }

.wall-fs-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: linear-gradient(180deg, rgba(0, 0, 0, .75) 0%, transparent 100%);
    color: #fff;
    transition: opacity .25s;
}

.wall-fs-bar.hidden { opacity: 0; pointer-events: none; }
.wall-fs-bar b { font-size: 15px; }
.wall-fs-bar span { color: rgba(255, 255, 255, .6); font-weight: 700; font-size: 13px; }

.wall-fs-exit {
    margin-left: auto;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, .16);
    background: transparent;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wall-fs-exit:hover { background: rgba(255, 255, 255, .12); }

.wall-panel {
    flex: 0 0 320px;
    width: 320px;
    background: #fff;
    border: 1px solid #D7DBEF;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(127, 127, 157, .10);
    overflow: hidden;
    position: sticky;
    top: 20px;
    height: calc(100vh - 180px);
    display: flex;
    flex-direction: column;
}

.wall-panel-block { padding: 14px 16px; border-bottom: 1px solid #D7DBEF; display: flex; flex-direction: column; min-height: 0; }
.wpb-profiles { flex: 0 0 auto; }
.wpb-cams { flex: 1 1 auto; border-bottom: 0; }
.wpb-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex: 0 0 auto; }
.wpb-head h4 { margin: 0; font-weight: 800; font-size: 13px; color: #1a1a2e; }

.wpb-add {
    width: 26px;
    height: 26px;
    border: 0;
    border-radius: 8px;
    background: #EAEDF8;
    color: #7381F4;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wpb-add:hover { background: #EFF1FF; }
.wprof-list, .wcam-list { overflow-y: auto; min-height: 0; margin: 0 -6px; padding: 0 6px; }
.wprof-list { max-height: 196px; }

.wprof {
    position: relative;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 8px;
    border-radius: 9px;
    border: 1px solid transparent;
}

.wprof:hover { background: #F1F3FB; }
.wprof.active { background: #EFF1FF; border-color: #C4DCFF; }
.wprof .wp-nm { flex: 1 1 auto; font-weight: 700; font-size: 13px; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; color: #1a1a2e; }
.wprof .wp-cnt { flex: 0 0 auto; font-weight: 700; font-size: 11px; color: #A6ABC2; }
.wp-cnt.accent { color: #7381F4; font-size: 13px; font-weight: 800; }

.wprof .wp-acts { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); display: none; gap: 1px; padding-left: 20px; background: linear-gradient(90deg, transparent, #F1F3FB 24%); border-radius: 9px; }
.wprof.active .wp-acts { background: linear-gradient(90deg, transparent, #EFF1FF 24%); }
.wprof:hover .wp-acts { display: flex; }

.wprof input {
    flex: 1 1 auto;
    min-width: 0;
    border: 1.5px solid #7381F4;
    border-radius: 8px;
    padding: 5px 8px;
    font-family: inherit;
    font-weight: 700;
    font-size: 13px;
    line-height: 1.4;
    outline: none;
    box-shadow: none;
}

.wprof input:focus { outline: none; box-shadow: none; }

.act {
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #6B7390;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

.act:hover { background: #EAEDF8; color: #1a1a2e; }
.act.danger:hover { background: #FDE9E8; color: #F04138; }

.wcam-search {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 10px;
    height: 36px;
    margin-bottom: 8px;
    flex: 0 0 auto;
    border: 1px solid #D7DBEF;
    border-radius: 9px;
    background: #F1F3FB;
    color: #ACACB6;
}

.wcam-search input {
    flex: 1 1 auto;
    min-width: 0;
    border: 0;
    border-radius: 0;
    padding: 0;
    background: transparent;
    outline: none;
    box-shadow: none;
    font-family: inherit;
    font-weight: 600;
    font-size: 13px;
    line-height: 1.4;
    color: #1a1a2e;
}

.wcam-search input:focus { border: 0; outline: none; box-shadow: none; }
.wcam-search:focus-within { border-color: #7381F4; background: #fff; box-shadow: 0 0 0 3px rgba(115, 129, 244, .16); }
.wcam-search-x { border: 0; background: transparent; color: #ACACB6; cursor: pointer; display: flex; padding: 2px; border-radius: 6px; }
.wcam-search-x:hover { background: #EAEDF8; color: #1a1a2e; }
.wcam-empty { padding: 24px 8px; text-align: center; font-weight: 600; font-size: 13px; color: #A6ABC2; }

.wcam {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 10px;
    border: 1px solid #D7DBEF;
    background: #fff;
    cursor: grab;
    margin-bottom: 7px;
}

.wcam:hover { border-color: #C4DCFF; box-shadow: 0 2px 8px rgba(127, 127, 157, .08); }
.wcam.placed { background: #EFF1FF; border-color: #C4DCFF; cursor: default; }
.wcam .wc-info { min-width: 0; flex: 1 1 auto; }
.wcam .wc-t { font-weight: 700; font-size: 13px; color: #1a1a2e; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.wcam .wc-s { font-weight: 600; font-size: 11.5px; color: #A6ABC2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.wall-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(26, 26, 46, .45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.wall-modal {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 80px rgba(127, 127, 157, .18);
    padding: 24px;
}

.wall-modal h3 { margin: 0 0 8px; font-size: 17px; font-weight: 700; color: #1a1a2e; }
.wall-modal p { margin: 0 0 20px; font-size: 13.5px; font-weight: 500; color: #6B7390; line-height: 1.5; }
.wall-modal-actions { display: flex; gap: 10px; }
.wall-modal-actions .btn { flex: 1; justify-content: center; }
</style>
