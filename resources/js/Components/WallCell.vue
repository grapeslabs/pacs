<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue';
import WhepPlayer from './WhepPlayer.vue';
import VideoPlayer from './VideoPlayer.vue';
import { createWhepSession, closeWhepSession } from '../whep';

const props = defineProps({
    stream: { type: Object, required: true },
    muted: { type: Boolean, default: true },
    paused: { type: Boolean, default: false },
    clickable: { type: Boolean, default: true },
    controls: { type: Boolean, default: true },
});

const emit = defineEmits(['toggle-audio', 'toggle-pause', 'open']);

const playerRef = ref(null);
const useHls = ref(false);
const status = ref('connecting');

const hlsSrc = computed(() => `/media/${props.stream.uid}/index.m3u8`);

const badge = computed(() => {
    if (status.value === 'error') return 'Офлайн';
    if (status.value !== 'live') return '···';
    if (props.paused) return 'Пауза';

    return useHls.value ? 'HLS' : 'Live';
});

const badgeTitle = computed(() => {
    if (status.value === 'error') return 'Нет связи с камерой';
    if (status.value !== 'live') return 'Подключение';
    if (props.paused) return 'Воспроизведение приостановлено';

    return useHls.value
        ? 'Резервный поток HLS: задержка около 30 секунд, идут повторные попытки вернуться на WebRTC'
        : 'WebRTC: трансляция в реальном времени';
});

const applyState = () => {
    const video = playerRef.value?.videoElement;

    if (!video) return;

    video.muted = props.muted;

    if (props.paused) video.pause();
    else video.play().catch(() => {});
};

const CONNECT_WATCHDOG = 20000;
const RETRY_BASE = 30000;
const RETRY_MAX = 300000;

let watchdog = null;
let retryTimer = null;
let retryDelay = RETRY_BASE;
let disposed = false;

const armWatchdog = () => {
    clearTimeout(watchdog);
    watchdog = setTimeout(() => {
        if (status.value === 'connecting') status.value = 'error';
    }, CONNECT_WATCHDOG);
};

const scheduleWhepRetry = () => {
    clearTimeout(retryTimer);

    const jitter = Math.round(Math.random() * 5000);

    retryTimer = setTimeout(async () => {
        retryDelay = Math.min(retryDelay * 2, RETRY_MAX);

        let probe = null;

        try {
            probe = await createWhepSession(props.stream.uid);
        } catch (e) {
            if (!disposed) scheduleWhepRetry();

            return;
        }

        closeWhepSession(probe);

        if (disposed) return;

        useHls.value = false;
        status.value = 'connecting';
        armWatchdog();
    }, retryDelay + jitter);
};

const onReady = () => {
    clearTimeout(watchdog);

    status.value = 'live';

    if (!useHls.value) {
        clearTimeout(retryTimer);
        retryDelay = RETRY_BASE;
    }

    applyState();
};

const onUnsupported = () => {
    if (useHls.value) {
        status.value = 'error';
        scheduleWhepRetry();

        return;
    }

    useHls.value = true;
    status.value = 'connecting';
    armWatchdog();
    scheduleWhepRetry();
    nextTick(applyState);
};

watch(() => props.muted, applyState);
watch(() => props.paused, applyState);

armWatchdog();

onBeforeUnmount(() => {
    disposed = true;
    clearTimeout(watchdog);
    clearTimeout(retryTimer);
});
</script>

<template>
    <div class="wall-cell" :class="{ clickable, paused }" @click="clickable && emit('open')">
        <div class="wall-cell-media">
            <WhepPlayer
                v-if="!useHls"
                ref="playerRef"
                :uid="stream.uid"
                :muted="muted"
                :paused="paused"
                @unsupported="onUnsupported"
                @ready="onReady"
            />
            <VideoPlayer
                v-else
                ref="playerRef"
                :src="hlsSrc"
                :muted="muted"
                live
                @ready="onReady"
                @error="onUnsupported"
            />
        </div>

        <div v-if="status !== 'live'" class="wc-state">
            <template v-if="status === 'connecting'">
                <span class="wc-spinner"></span>
                <span>Подключение…</span>
            </template>
            <template v-else>
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"></path><path d="M14 5H3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2z"></path><line x1="2" y1="2" x2="22" y2="22"></line></svg>
                <span>Нет сигнала</span>
            </template>
        </div>

        <span class="wc-name">{{ stream.name }}</span>
        <span
            class="wc-live"
            :class="{ off: paused || status !== 'live', err: status === 'error', hls: badge === 'HLS' }"
            :title="badgeTitle"
        >
            <span class="d"></span>{{ badge }}
        </span>

        <div v-if="controls && status !== 'error'" class="wc-tools">
            <button
                type="button"
                class="wc-tool"
                :class="{ on: paused }"
                :title="paused ? 'Продолжить' : 'Пауза'"
                @click.stop="emit('toggle-pause')"
            >
                <svg v-if="paused" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"></path></svg>
                <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1"></rect><rect x="14" y="4" width="4" height="16" rx="1"></rect></svg>
            </button>
            <button
                type="button"
                class="wc-tool"
                :class="{ on: !muted }"
                :title="muted ? 'Включить звук' : 'Выключить звук'"
                @click.stop="emit('toggle-audio')"
            >
                <svg v-if="muted" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>
                <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path><path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path></svg>
            </button>
        </div>
    </div>
</template>

<style scoped>
.wall-cell {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background: #1B1A2A;
    box-shadow: 0 2px 8px rgba(127, 127, 157, .08);
    min-width: 0;
    min-height: 0;
}

.wall-cell::before {
    content: "";
    position: absolute;
    inset: 0;
    border: 0 solid transparent;
    border-radius: inherit;
    pointer-events: none;
    z-index: 5;
    transition: border-color .15s;
}

.wall-cell.clickable { cursor: pointer; }
.wall-cell.clickable:hover::before { border-width: 2px; border-color: #C4DCFF; }

.wall-cell-media { position: absolute; inset: 0; }
.wall-cell-media > * { width: 100%; height: 100%; }
.wall-cell.paused .wall-cell-media { filter: grayscale(1) brightness(.45); }

.wc-name {
    position: absolute;
    top: 8px;
    left: 8px;
    background: rgba(0, 0, 0, .58);
    color: #fff;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 11px;
    max-width: calc(100% - 90px);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    z-index: 3;
}

.wc-live {
    position: absolute;
    top: 8px;
    right: 8px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: linear-gradient(180deg, #7E92F8 0%, #6972F0 100%);
    color: #fff;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 10px;
    z-index: 3;
}

.wc-live.off { background: #4B4B6C; }
.wc-live.err { background: #F04138; }
.wc-live.hls { background: #E0A32E; }

.wc-state {
    position: absolute;
    inset: 0;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: rgba(255, 255, 255, .55);
    font-weight: 600;
    font-size: 12px;
    pointer-events: none;
}

.wc-spinner {
    width: 26px;
    height: 26px;
    border: 2.5px solid rgba(255, 255, 255, .18);
    border-top-color: rgba(255, 255, 255, .7);
    border-radius: 50%;
    animation: wc-spin .9s linear infinite;
}

@keyframes wc-spin {
    to { transform: rotate(360deg); }
}

.wc-live .d { width: 6px; height: 6px; border-radius: 50%; background: #fff; }
.wc-live:not(.off) .d { animation: wc-blink 1.4s infinite; }

@keyframes wc-blink {
    0%, 100% { opacity: 1; }
    50% { opacity: .25; }
}

.wc-tools {
    position: absolute;
    bottom: 8px;
    right: 8px;
    display: flex;
    gap: 6px;
    z-index: 4;
    opacity: 0;
    transition: opacity .12s;
}

.wall-cell:hover .wc-tools { opacity: 1; }

.wc-tool {
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 8px;
    background: rgba(0, 0, 0, .55);
    color: rgba(255, 255, 255, .8);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .12s, color .12s;
}

.wc-tool:hover { background: rgba(255, 255, 255, .16); color: #fff; }
.wc-tool.on { opacity: 1; background: linear-gradient(180deg, #7E92F8 0%, #6972F0 100%); color: #fff; }
.wc-tools:has(.wc-tool.on) { opacity: 1; }
</style>
