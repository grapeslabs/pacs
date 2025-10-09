<script setup>
import { ref, computed, watch, onBeforeUnmount, nextTick } from 'vue';
import { format, startOfDay, getUnixTime, addDays, isValid } from 'date-fns';
import axios from 'axios';

import DatePicker from './DatePicker.vue';
import VideoPlayer from './VideoPlayer.vue';

const props = defineProps({
    camera: { type: Object, required: true }
});
const playerRef = ref(null);
const containerRef = ref(null);
const viewportRef = ref(null);
const trackRef = ref(null);

const isPlaying = ref(false);
const isLive = ref(true);
const volume = ref(50);
const speed = ref(1);
const isSpeedMenuOpen = ref(false);
const speeds = [16, 8, 4, 2, 1, 0.5, 0.25];

const toggleSpeedMenu = () => {
    isSpeedMenuOpen.value = !isSpeedMenuOpen.value;
};

const selectSpeed = (val) => {
    speed.value = val;
    isSpeedMenuOpen.value = false;
};

const selectedDate = ref(new Date().toISOString().split('T')[0]);

const timelineSegments = ref([]);
const parsedSegmentsMap = ref([]);
const timelineProgress = ref(0);
const currentTimeDisplay = ref("");

const timelineScale = ref(1);
const panX = ref(0);

const isDraggingTrack = ref(false);
const startDragX = ref(0);
const startPanX = ref(0);
const isDraggingPlayhead = ref(false);

const showDownloadModal = ref(false);
const downloadRange = ref({ start: 0, end: 0 });
const isDraggingAnchor = ref(null);

const secToTime = (sec) => {
    const h = Math.floor(sec / 3600).toString().padStart(2, '0');
    const m = Math.floor((sec % 3600) / 60).toString().padStart(2, '0');
    const s = Math.floor(sec % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
};
const timeToSec = (timeStr) => {
    if(!timeStr) return 0;
    const [h, m, s] = timeStr.split(':').map(Number);
    return (h * 3600) + (m * 60) + (s || 0);
};

const downloadStartInput = computed({
    get: () => secToTime(downloadRange.value.start),
    set: (val) => {
        const sec = timeToSec(val);
        downloadRange.value.start = Math.max(0, Math.min(sec, downloadRange.value.end - 30));
    }
});
const downloadEndInput = computed({
    get: () => secToTime(downloadRange.value.end),
    set: (val) => {
        const sec = timeToSec(val);
        downloadRange.value.end = Math.min(86400, Math.max(sec, downloadRange.value.start + 30));
    }
});

const validDownloadSeconds = computed(() => {
    let totalValidSec = 0;
    const selStart = downloadRange.value.start;
    const selEnd = downloadRange.value.end;

    for (const seg of parsedSegmentsMap.value) {
        if (seg.isGap) continue;

        const overlapStart = Math.max(seg.start, selStart);
        const overlapEnd = Math.min(seg.end, selEnd);

        if (overlapStart < overlapEnd) {
            totalValidSec += (overlapEnd - overlapStart);
        }
    }
    return Math.round(totalValidSec);
});

const downloadDurationText = computed(() => {
    const totalSec = validDownloadSeconds.value;
    if (totalSec === 0) return "0 секунд";

    const h = Math.floor(totalSec / 3600);
    const m = Math.floor((totalSec % 3600) / 60);
    const s = totalSec % 60;

    const pluralize = (num, words) => {
        const n = Math.abs(num) % 100;
        const n1 = n % 10;
        if (n > 10 && n < 20) return words[2];
        if (n1 > 1 && n1 < 5) return words[1];
        if (n1 === 1) return words[0];
        return words[2];
    };

    let textParts = [];
    if (h > 0) textParts.push(`${h} ${pluralize(h, ['час', 'часа', 'часов'])}`);
    if (m > 0) textParts.push(`${m} ${pluralize(m, ['минута', 'минуты', 'минут'])}`);
    if (s > 0 || (h === 0 && m === 0)) textParts.push(`${s} ${pluralize(s, ['секунда', 'секунды', 'секунд'])}`);

    return textParts.join(' ');
});

const downloadStatusText = ref('Ожидание');

const openDownloadMode = () => {
    if (showDownloadModal.value) {
        showDownloadModal.value = false;
        return;
    }

    if (isLive.value) toggleLive();

    if (isPlaying.value) togglePlay();

    const currentSec = (timelineProgress.value / 100) * 86400;
    downloadRange.value.start = Math.max(0, currentSec - 300);
    downloadRange.value.end = Math.min(86400, currentSec + 300);
    showDownloadModal.value = true;
};

const delay = (ms) => new Promise(res => setTimeout(res, ms));
const executeDownload = async () => {
    downloadStatusText.value = "Подготовка запроса...";

    const dateBaseUnix = getUnixTime(startOfDay(new Date(selectedDate.value)));
    const startTimestamp = dateBaseUnix + downloadRange.value.start;
    const endTimestamp = dateBaseUnix + downloadRange.value.end;

    try {
        let result;
        result = await axios.post(`/streams/archive-download/${props.camera.id}`, {
            start_time: startTimestamp,
            end_time: endTimestamp
        });
        const requestId = result.data?.requestId;
        if (!requestId) throw new Error("Ошибка подготовки запроса!");
        downloadStatusText.value = 'Подготовка файла...';
        let attempts = 0;
        const maxAttempts = 300;
        let archiveInfo = null;
        while (attempts < maxAttempts) {
            archiveInfo = await axios.get(`/streams/download-status/${props.camera.id}`, {
                params: { requestId: requestId }
            });
            if (archiveInfo.data?.status === 'Completed') break;
            if (archiveInfo.data?.status === 'NotFound') {
                throw new Error(archiveInfo.data.error || "Ошибка генерации архива!");
            }
            attempts++;
            await delay(2000);
        }

        if (attempts >= maxAttempts) {
            throw new Error("Превышено время ожидания!");
        }
        downloadStatusText.value = 'Скачивание файла...';
        let fileInfo = null;
        fileInfo = await axios.get(`/streams/download-file/${props.camera.id}`, {
            params: { requestId: requestId }
        });

        const downloadUrl = fileInfo.data.url;
        if (!downloadUrl) throw new Error("Ошибка скачивания файла");

        let progress;
        const response = await axios.get(downloadUrl, {
            responseType: 'blob',
            onDownloadProgress: (progressEvent) => {
                if (progressEvent.lengthComputable) {
                    progress = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                    downloadStatusText.value = `Скачивание файла - ${progress}%`;
                }
            },
        });

        const blob = response.data;
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = requestId;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);

        downloadStatusText.value = 'Ожидание';
        showDownloadModal.value = false;
    } catch (error) {
        downloadStatusText.value = error.message || error;
    }
};

const startDragAnchor = (type, event) => {
    event.stopPropagation();
    isDraggingAnchor.value = type;
    document.addEventListener('mousemove', onDragAnchor);
    document.addEventListener('mouseup', stopDragAnchor);
};

const onDragAnchor = (event) => {
    if (!isDraggingAnchor.value || !trackRef.value) return;

    const rect = trackRef.value.getBoundingClientRect();
    let pct = (event.clientX - rect.left) / rect.width;
    pct = Math.max(0, Math.min(1, pct));
    const sec = Math.round(pct * 86400);
    if (isDraggingAnchor.value === 'start') {
        downloadRange.value.start = Math.min(sec, downloadRange.value.end - 30);
    } else {
        downloadRange.value.end = Math.max(sec, downloadRange.value.start + 30);
    }
};

const stopDragAnchor = () => {
    isDraggingAnchor.value = null;
    document.removeEventListener('mousemove', onDragAnchor);
    document.removeEventListener('mouseup', stopDragAnchor);
};
// ====================================================

const timeMarkers = computed(() => {
    let intervalMins = 120;
    if (timelineScale.value >= 16) {
        intervalMins = 5;
    } else if (timelineScale.value >= 8) {
        intervalMins = 15;
    } else if (timelineScale.value >= 4) {
        intervalMins = 30;
    } else if (timelineScale.value >= 2) {
        intervalMins = 60;
    }

    const arr = [];
    const totalMins = 24 * 60;
    for (let m = 0; m <= totalMins; m += intervalMins) {
        const hours = Math.floor(m / 60);
        const mins = m % 60;
        const label = ('0' + hours).slice(-2) + ':' + ('0' + mins).slice(-2);
        const percent = (m / totalMins) * 100;
        arr.push({ label, percent });
    }

    return arr;
});

const streamUrl = computed(() => {
    if (!props.camera?.uid) return null;
    if (isLive.value) {
        return `/media/${props.camera.uid}/index.m3u8`;
    } else {
        const dateObj = new Date(selectedDate.value);
        if (!isValid(dateObj)) return null;
        const startTimestamp = getUnixTime(startOfDay(dateObj));
        if (isNaN(startTimestamp)) return null;
        const duration = 86400;
        return `/media/${props.camera.uid}/index-${startTimestamp}-${duration}.m3u8?_t=${Date.now()}`;
    }
});
const fetchAndParseArchive = async (url) => {
    timelineSegments.value = [];
    parsedSegmentsMap.value = [];
    if (isLive.value || !url) return;

    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error("Manifest load failed");
        const text = await response.text();

        const lines = text.split('\n');
        let currentTimeSec = 0;
        let segmentDuration = 0;
        let isGap = false;
        const daySeconds = 86400;

        const visualSegments = [];
        const dataSegments = [];

        const baseUrl = url.split('?')[0].substring(0, url.lastIndexOf('/') + 1);

        for (let line of lines) {
            line = line.trim();
            if (!line) continue;

            if (line.startsWith('#EXTINF:')) {
                segmentDuration = parseFloat(line.split(':')[1].replace(',', ''));
            } else if (line.startsWith('#EXT-X-GAP')) {
                isGap = true;
            } else if (!line.startsWith('#')) {
                const fullUrl = line.startsWith('http') ? line : baseUrl + line;

                dataSegments.push({
                    start: currentTimeSec,
                    end: currentTimeSec + segmentDuration,
                    url: fullUrl,
                    isGap: isGap
                });

                const widthPct = (segmentDuration / daySeconds) * 100;
                const startPct = (currentTimeSec / daySeconds) * 100;
                const type = isGap ? 'gap' : 'record';

                const last = visualSegments[visualSegments.length - 1];
                if (last && last.type === type && Math.abs((last.start + last.width) - startPct) < 0.001) {
                    last.width += widthPct;
                } else {
                    visualSegments.push({ start: startPct, width: widthPct, type });
                }

                currentTimeSec += segmentDuration;
                isGap = false;
            }
        }

        timelineSegments.value = visualSegments;
        parsedSegmentsMap.value = dataSegments;
    } catch (e) {
        console.error("Archive parse error:", e);
    }
};

const updateDisplayTimeStr = (percent) => {
    const dateBase = startOfDay(new Date(selectedDate.value));
    if (!isValid(dateBase)) {
        currentTimeDisplay.value = "--:--:--";
        return;
    }
    const seconds = (percent / 100) * 86400;
    const displayDate = new Date(dateBase.getTime() + seconds * 1000);
    currentTimeDisplay.value = format(displayDate, 'dd.MM.yyyy HH:mm:ss');
};

const onTimeUpdate = (currentTime) => {
    if (isDraggingPlayhead.value) return;

    if (isLive.value) {
        currentTimeDisplay.value = format(Date.now(), 'dd.MM.yyyy HH:mm:ss');
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        timelineProgress.value = ((now - start) / (1000 * 60 * 60 * 24)) * 100;
    } else {
        timelineProgress.value = (currentTime / 86400) * 100;
        updateDisplayTimeStr(timelineProgress.value);
    }
};

const setScale = (newScale) => {
    timelineScale.value = Math.max(1, Math.min(newScale, 24));
    nextTick(() => centerTimeline());
};

const zoomIn = () => setScale(timelineScale.value * 1.5);
const zoomOut = () => setScale(timelineScale.value / 1.5);

const centerTimeline = () => {
    if (!viewportRef.value) return;
    const viewportWidth = viewportRef.value.clientWidth;
    const trackWidth = viewportWidth * timelineScale.value;
    const playheadPixelPos = (timelineProgress.value / 100) * trackWidth;

    let newPanX = (viewportWidth / 2) - playheadPixelPos;

    const minPan = -(trackWidth - viewportWidth);
    panX.value = Math.max(minPan, Math.min(0, newPanX));
};

const onTrackMouseDown = (e) => {
    if (isLive.value || e.button !== 0) return;
    if (e.target.closest('.playhead') || e.target.closest('.download-anchor')) return;

    isDraggingTrack.value = true;
    startDragX.value = e.clientX;
    startPanX.value = panX.value;

    window.addEventListener('mousemove', onTrackMouseMove);
    window.addEventListener('mouseup', onTrackMouseUp);
};

const onTrackMouseMove = (e) => {
    if (!isDraggingTrack.value || !viewportRef.value) return;

    const dx = e.clientX - startDragX.value;
    const viewportWidth = viewportRef.value.clientWidth;
    const trackWidth = viewportWidth * timelineScale.value;

    let newPanX = startPanX.value + dx;
    const minPan = -(trackWidth - viewportWidth);

    panX.value = Math.max(minPan, Math.min(0, newPanX));
    console.log(panX.value);
};

const onTrackMouseUp = (e) => {
    if (!isDraggingTrack.value) return;
    isDraggingTrack.value = false;
    window.removeEventListener('mousemove', onTrackMouseMove);
    window.removeEventListener('mouseup', onTrackMouseUp);

    if (Math.abs(e.clientX - startDragX.value) < 5 && !showDownloadModal.value) {
        seekFromMouseEvent(e.clientX);
    }
};

const onPlayheadMouseDown = (e) => {
    if (isLive.value || e.button !== 0 || showDownloadModal.value) return;
    e.stopPropagation();
    isDraggingPlayhead.value = true;

    window.addEventListener('mousemove', onPlayheadMouseMove);
    window.addEventListener('mouseup', onPlayheadMouseUp);
};

const seekFromMouseEvent = (clientX) => {
    if (!viewportRef.value) return;
    const rect = viewportRef.value.getBoundingClientRect();
    const trackWidth = rect.width * timelineScale.value;

    const trackLeftEdge = rect.left + panX.value;
    let xOnTrack = clientX - trackLeftEdge;

    xOnTrack = Math.max(0, Math.min(xOnTrack, trackWidth));
    const percent = (xOnTrack / trackWidth) * 100;

    timelineProgress.value = percent;
    updateDisplayTimeStr(percent);

    return percent;
};

const onPlayheadMouseMove = (e) => {
    if (!isDraggingPlayhead.value) return;
    seekFromMouseEvent(e.clientX);
};

const onPlayheadMouseUp = (e) => {
    if (!isDraggingPlayhead.value) return;
    isDraggingPlayhead.value = false;

    window.removeEventListener('mousemove', onPlayheadMouseMove);
    window.removeEventListener('mouseup', onPlayheadMouseUp);

    const percent = seekFromMouseEvent(e.clientX);
    const seekSeconds = (percent / 100) * 86400;
    console.log(percent, seekSeconds);
    playerRef.value?.seek(seekSeconds);

};

const togglePlay = () => {
    if (isPlaying.value) playerRef.value?.pause();
    else playerRef.value?.play();
};

const toggleLive = () => {
    isLive.value = !isLive.value;
    if (isLive.value) {
        selectedDate.value = new Date().toISOString().split('T')[0];
        showDownloadModal.value = false;
        setScale(1);
    }
};

const handleDateChange = (date) => {
    selectedDate.value = date;
    isLive.value = false;
};

const makeScreenshot = () => {
    const data = playerRef.value?.takeScreenshot();
    if (data) {
        const link = document.createElement('a');
        link.href = data;
        link.download = `screenshot_${props.camera.name}_${Date.now()}.jpg`;
        link.click();
    }
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) containerRef.value?.requestFullscreen();
    else document.exitFullscreen();
};

const changeDate = (days) => {
    const current = new Date(selectedDate.value);
    if (!isValid(current)) {
        selectedDate.value = new Date().toISOString().split('T')[0];
        return;
    }
    const nextDate = addDays(current, days);
    selectedDate.value = format(nextDate, 'yyyy-MM-dd');
    isLive.value = false;
};

watch(streamUrl, (newUrl) => {
    if (!isLive.value && newUrl) {
        fetchAndParseArchive(newUrl);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('mousemove', onTrackMouseMove);
    window.removeEventListener('mouseup', onTrackMouseUp);
    window.removeEventListener('mousemove', onPlayheadMouseMove);
    window.removeEventListener('mouseup', onPlayheadMouseUp);
    stopDragAnchor();
});

const setPlaying = (val) => isPlaying.value = val;
</script>

<template>
    <div class="video-player-wrapper" ref="containerRef">
        <div class="video-screen">
            <template v-if="streamUrl">
                <div class="video-content">
                    <VideoPlayer
                        ref="playerRef"
                        :src="streamUrl"
                        :volume="volume / 100"
                        :playback-rate="speed"
                        :muted='false'
                        @timeupdate="onTimeUpdate"
                        @play="setPlaying(true)"
                        @pause="setPlaying(false)"
                    />
                </div>
            </template>
            <template v-else>
                <div class="video-content error-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <path d="M23 7L16 12L23 17V7Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 5H3C1.89543 5 1 5.89543 1 7V17C1 18.1046 1.89543 19 3 19H14C15.1046 19 16 18.1046 16 17V7C16 5.89543 15.1046 5 14 5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="2" y1="2" x2="22" y2="22" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span class="error-text">Нет сигнала</span>
                </div>
            </template>

            <div v-if="showDownloadModal" class="download-modal-overlay">
                <div class="download-modal">
                    <div class="dm-header">
                        <h3 class="dm-title">Выберите сегмент</h3>
                        <button class="dm-close" @click="showDownloadModal = false" title="Закрыть">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    <div class="dm-body">
                        <div class="dm-time-inputs">
                            <input type="time" step="1" v-model="downloadStartInput" class="dm-input" />
                            <span class="dm-separator">-</span>
                            <input type="time" step="1" v-model="downloadEndInput" class="dm-input" />
                        </div>
                    </div>

                    <div class="dm-divider"></div>

                    <div class="dm-footer">
                        <div class="dm-duration-wrap">
                            <span class="dm-duration-label">Доступно:</span>
                            <span class="dm-duration" :class="{'error': validDownloadSeconds === 0}">
                                {{ downloadDurationText }}
                            </span>
                        </div>
                        <div class="dm-duration-wrap">
                            <span class="dm-duration-label">Статус:</span>
                            <span class="dm-duration">
                                {{ downloadStatusText }}
                            </span>
                        </div>
                        <button class="dm-download-btn" @click="executeDownload" :disabled="validDownloadSeconds === 0">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="controls-overlay">
                <div class="timeline-container">
                    <div class="timeline-viewport" ref="viewportRef">
                        <div
                            class="timeline-track"
                            ref="trackRef"
                            :class="{ 'is-grabbing': isDraggingTrack, 'is-live': isLive }"
                            :style="{ width: `${timelineScale * 100}%`, transform: `translateX(${panX}px)` }"
                            @mousedown="onTrackMouseDown"
                        >
                            <div class="time-labels">
                                <span
                                    v-for="(marker, idx) in timeMarkers"
                                    :key="idx"
                                    :style="{ left: `${marker.percent}%` }"
                                >
                                    {{ marker.label }}
                                </span>
                            </div>

                            <div class="track-bg"></div>

                            <div
                                v-if="!isLive"
                                v-for="(seg, idx) in timelineSegments"
                                :key="idx"
                                class="track-segment"
                                :class="seg.type"
                                :style="{ left: seg.start + '%', width: seg.width + '%' }"
                            ></div>

                            <template v-if="showDownloadModal">
                                <div class="download-selection-bg" :style="{
                                    left: (downloadRange.start / 86400 * 100) + '%',
                                    width: ((downloadRange.end - downloadRange.start) / 86400 * 100) + '%'
                                }"></div>

                                <div class="download-anchor left-anchor"
                                     :style="{ left: (downloadRange.start / 86400 * 100) + '%' }"
                                     @mousedown="startDragAnchor('start', $event)">
                                    <div class="anchor-grip"></div>
                                </div>

                                <div class="download-anchor right-anchor"
                                     :style="{ left: (downloadRange.end / 86400 * 100) + '%' }"
                                     @mousedown="startDragAnchor('end', $event)">
                                    <div class="anchor-grip"></div>
                                </div>
                            </template>

                            <div v-show="!showDownloadModal" class="playhead" :style="{ left: timelineProgress + '%' }">
                                <div class="playhead-line"></div>
                                <div
                                    class="playhead-knob"
                                    :class="{ 'is-dragging': isDraggingPlayhead }"
                                    @mousedown="onPlayheadMouseDown"
                                ></div>
                                <div class="playhead-tooltip" v-if="currentTimeDisplay">
                                    <span class="tooltip-time">{{ currentTimeDisplay }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="controls-bar">
                    <div class="controls-left">
                        <div class="speed-control-wrapper">
                            <div v-if="isSpeedMenuOpen" class="speed-dropdown-menu">
                                <div v-for="s in speeds" :key="s"  class="speed-option" :class="{ active: speed === s }" @click="selectSpeed(s)">
                                    x{{ s }}
                                </div>
                            </div>

                            <button class="ctrl-btn speed-btn" @click="toggleSpeedMenu" :class="{ 'btn-active': isSpeedMenuOpen }">
                                x{{ speed }}
                            </button>
                        </div>                        <div class="volume-control">
                            <button class="ctrl-btn icon-only" @click="volume = volume === 0 ? 50 : 0" title="Громкость">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M15.4001 8.20006C15.8419 7.86869 16.4687 7.95823 16.8001 8.40006C17.579 9.43864 18.0001 10.7018 18.0001 12.0001C18.0001 13.2983 17.579 14.5615 16.8001 15.6001C16.4687 16.0419 15.8419 16.1314 15.4001 15.8001C14.9582 15.4687 14.8687 14.8419 15.2001 14.4001C15.7193 13.7077 16.0001 12.8655 16.0001 12.0001C16.0001 11.1346 15.7193 10.2924 15.2001 9.60006C14.8687 9.15823 14.9582 8.53143 15.4001 8.20006Z" fill="#888298"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M18.6562 4.92888C19.0467 4.53836 19.6799 4.53835 20.0704 4.92888C20.999 5.85746 21.7356 6.95986 22.2381 8.17312C22.7407 9.38639 22.9994 10.6868 22.9994 12C22.9994 13.3132 22.7407 14.6136 22.2381 15.8268C21.7356 17.0401 20.999 18.1425 20.0704 19.0711C19.6799 19.4616 19.0467 19.4616 18.6562 19.0711C18.2656 18.6806 18.2657 18.0474 18.6562 17.6569C19.3991 16.914 19.9883 16.0321 20.3904 15.0615C20.7924 14.0909 20.9994 13.0506 20.9994 12C20.9994 10.9494 20.7924 9.90911 20.3904 8.9385C19.9883 7.96788 19.3991 7.08597 18.6562 6.3431C18.2657 5.95258 18.2656 5.31941 18.6562 4.92888Z" fill="#888298"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.4332 4.09871C11.7797 4.26523 12 4.6156 12 5.00001V19C12 19.3844 11.7797 19.7348 11.4332 19.9013C11.0867 20.0678 10.6755 20.021 10.3753 19.7809L5.64922 16H2C1.44772 16 1 15.5523 1 15V9.00001C1 8.44772 1.44772 8.00001 2 8.00001H5.64922L10.3753 4.21914C10.6755 3.979 11.0867 3.93219 11.4332 4.09871ZM10 7.08063L6.62469 9.78088C6.44738 9.92273 6.22707 10 6 10H3V14H6C6.22707 14 6.44738 14.0773 6.62469 14.2191L10 16.9194V7.08063Z" fill="#888298"/>
                                </svg>
                            </button>
                            <div class="volume-slider">
                                <input type="range"  min="0"  max="100" v-model.number="volume" :style="{ background: `linear-gradient(to right, #fff ${volume}%, rgba(255, 255, 255, 0.2) ${volume}%)` }">                            </div>
                        </div>
                    </div>

                    <div class="controls-center">
                        <div class="playback-group">
                            <div class="zoom-controls" v-if="!isLive">
                                <button class="ctrl-btn icon-only small" @click="zoomOut" title="Уменьшить масштаб">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </button>
                                <span class="zoom-text">{{ Math.round(timelineScale * 100) }}%</span>
                                <button class="ctrl-btn icon-only small" @click="zoomIn" title="Увеличить масштаб">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </button>
                            </div>
                            <button class="ctrl-btn icon-only small" @click="changeDate(-1)" title="Предыдущий день">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <button class="play-pause-btn" @click="togglePlay" title="Воспроизведение">
                                <svg v-if="!isPlaying" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3L19 12L5 21V3Z"/></svg>
                                <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                            </button>
                            <button class="ctrl-btn icon-only small" @click="changeDate(1)" title="Следующий день">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                            <button v-if="!isLive" class="ctrl-btn icon-only small" @click="centerTimeline" title="Установить по центру">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M22 12C22 17.523 17.523 22 12 22C6.477 22 2 17.523 2 12C2 6.477 6.477 2 12 2C17.523 2 22 6.477 22 12Z" stroke="#7F7F9D" stroke-width="1.5"/>
                                    <path d="M2 12H5M19 12H22M12 22V19M12 5V2" stroke="#7F7F9D" stroke-width="1.5" stroke-linecap="round"/>
                                    <path d="M10 12H14M12 14V10" stroke="#7F7F9D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                        <button class="live-badge" :class="{ 'active': isLive }" @click="toggleLive" title='Перейти к онлайну'>
                            <div class="live-dot"></div> LIVE
                        </button>
                        <div style="position: relative;" title="Выбрать дату">
                            <DatePicker :modelValue="selectedDate"  @change="handleDateChange"/>
                        </div>
                    </div>

                    <div class="controls-right">
                        <button class="ctrl-btn icon-only" title="Скачать" @click="openDownloadMode" :class="{'active': showDownloadModal}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </button>
                        <button class="ctrl-btn icon-only" title="Скриншот" @click="makeScreenshot">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </button>
                        <button class="ctrl-btn icon-only" @click="toggleFullscreen" title="Полноэкранный режим">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.video-player-wrapper {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
    height: 60%;
    background: #F8F9FC;
    user-select: none;

    &:fullscreen {
        background: black;

        .player-header {
            display: none;
        }

        .video-screen {
            height: 100vh;
            border-radius: 0;
        }
    }
}

.video-screen {
    flex: 1;
    background: #000;
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding-bottom: 123px;
}

.video-content {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.controls-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: #2B2B36;
    padding: 0 0 16px 0;
    display: flex;
    flex-direction: column;
    z-index: 10;
}

.timeline-container {
    position: relative;
    height: 75px;
    background: rgba(43, 43, 54, 0.95);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 12px;
    padding: 0 16px;
    margin-top: 12px;
}

.timeline-viewport {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.timeline-track {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    height: 100%;
    cursor: grab;
    transform-origin: left center;

    &.is-live {
        cursor: default;
    }

    &.is-grabbing {
        cursor: grabbing;
    }
}

.time-labels {
    position: absolute;
    top: 10px;
    left: 0;
    right: 0;
    pointer-events: none;

    span {
        position: absolute;
        transform: translateX(-50%);
        font-size: 11px;
        color: #A1A4C4;
    }
}

.track-bg {
    position: absolute;
    bottom: 16px;
    left: 0;
    right: 0;
    height: 8px;
    background: #4B4B6C;
    border-radius: 4px;
}

.track-segment {
    position: absolute;
    bottom: 16px;
    height: 8px;

    &.record {
        background: #27AE60;
    }

    &.gap {
        background: #E03C3C;
        opacity: 0.6;
    }
}

.playhead {
    position: absolute;
    bottom: 16px;
    width: 0;
    height: 8px;
    z-index: 10;
}

.playhead-knob {
    position: absolute;
    left: -8px;
    top: -4px;
    width: 16px;
    height: 16px;
    background: #E03C3C;
    border: 2px solid #2B2B36;
    border-radius: 50%;
    box-shadow: 0 0 4px rgba(0, 0, 0, 0.5);
    cursor: grab;
    transition: transform 0.1s;

    &:hover, &.is-dragging {
        transform: scale(1.2);
    }

    &.is-dragging {
        cursor: grabbing;
    }
}

.playhead-line {
    position: absolute;
    left: 0;
    bottom: 4px;
    width: 1px;
    height: 36px;
    background: #E03C3C;
    pointer-events: none;
}

.playhead-tooltip {
    position: absolute;
    bottom: 38px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(43, 43, 54, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 4px 8px;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    white-space: nowrap;
    pointer-events: none;
    z-index: 100;
}

.tooltip-time {
    font-size: 12px;
    color: #FFFFFF;
    font-weight: 600;
}

.download-selection-bg {
    position: absolute;
    bottom: 16px;
    height: 8px;
    background: #6366f1;
    opacity: 0.4;
    pointer-events: none;
    z-index: 5;
}

.download-anchor {
    position: absolute;
    bottom: 12px;
    height: 16px;
    width: 12px;
    cursor: ew-resize;
    z-index: 11;
    display: flex;
    align-items: center;
    justify-content: center;

    .anchor-grip {
        width: 6px;
        height: 16px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 0 4px rgba(0, 0, 0, 0.4);
        border: 1px solid #d1d5db;
    }

    &:hover .anchor-grip {
        background: #eef2ff;
        border-color: #6366f1;
    }
}

.download-anchor.left-anchor {
    transform: translateX(-100%);
}

.download-anchor.right-anchor {
    transform: translateX(0);
}

.download-modal-overlay {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 130px;
    display: flex;
    justify-content: center;
    pointer-events: none;
    z-index: 20;
}

.download-modal {
    pointer-events: auto;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    padding: 16px 20px;
    width: 340px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: #1f2937;
    animation: slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(15px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.dm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.dm-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    color: #111827;
}

.dm-close {
    background: transparent;
    border: none;
    border-radius: 8px;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;

    &:hover {
        background: #f3f4f6;
    }
}

.dm-body {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dm-time-inputs {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dm-input {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 15px;
    color: #111827;
    outline: none;
    text-align: center;
    width: 42%;
    font-family: monospace;
    transition: all 0.2s;

    &:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }
}

.dm-separator {
    color: #9ca3af;
    font-weight: 500;
}

.dm-divider {
    height: 1px;
    background: #f3f4f6;
    margin: 16px 0;
}

.dm-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dm-duration-wrap {
    display: flex;
    flex-direction: column;
}

.dm-duration-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 2px;
}

.dm-duration {
    font-size: 14px;
    color: #4f46e5;
    font-weight: 600;
}

.dm-duration.error {
    color: #ef4444;
}

.dm-download-btn {
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 10px;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: #4f46e5;
        transform: scale(1.05);
    }

    &:disabled {
        background: #d1d5db;
        cursor: not-allowed;
        opacity: 0.7;
    }
}

.controls-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 24px;
}

.controls-left, .controls-center, .controls-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.ctrl-btn {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #BDBCDB;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover, &.active {
        border-color: #fff;
        color: #fff;
    }

    &.icon-only {
        width: 36px;
        height: 36px;
        border: none;
    }

    &.small {
        width: 32px;
        height: 32px;
    }
}

.volume-control {
    display: flex;
    align-items: center;
    gap: 10px;
}

.volume-slider {
    padding-bottom: 0.2rem;
}

.volume-slider input[type="range"] {
    -webkit-appearance: none;
    appearance: none;
    width: 100px;
    height: 6px;
    border-radius: 10px;
    outline: none;
    cursor: pointer;
}

.volume-slider input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 0;
    height: 0;
}

.volume-slider input[type="range"]::-moz-range-thumb {
    width: 0;
    height: 0;
    border: none;
}

.playback-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.zoom-controls {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-right: 8px;
    background: rgba(255, 255, 255, 0.05);
    padding: 2px 6px;
    border-radius: 6px;

    .zoom-text {
        color: #A1A4C4;
        font-size: 12px;
        min-width: 32px;
        text-align: center;
    }
}

.play-pause-btn {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;

    &:hover {
        background: rgba(255, 255, 255, 0.2);
    }
}

.live-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #4B4B6C;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;

    &.active {
        background: #6972F0;
    }
}

.live-dot {
    width: 6px;
    height: 6px;
    background: white;
    border-radius: 50%;
}

@media (max-width: 768px) {
    .time-labels span:nth-child(even) {
        display: none;
    }

    .controls-bar {
        gap: 8px;
        padding: 0 12px;
    }

    .controls-left, .controls-right {
        gap: 8px;
    }

    .ctrl-btn.icon-only {
        width: 32px;
        height: 32px;
    }

    .play-pause-btn {
        width: 40px;
        height: 40px;
    }
}

.video-content.error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: #1a1a1a;
}

.error-text {
    color: #fff;
    font-size: 14px;
    opacity: 0.8;
}

.speed-control-wrapper {
    position: relative;
    display: inline-block;
}

.speed-dropdown-menu {
    position: absolute;
    bottom: 110%;
    left: 0;
    background: rgba(30, 30, 40, 0.95);
    border: 1px solid #444;
    border-radius: 4px;
    padding: 4px 0;
    min-width: 60px;
    z-index: 100;
    display: flex;
    flex-direction: column-reverse;
}

.speed-option {
    padding: 4px 12px;
    color: #ccc;
    cursor: pointer;
    font-size: 13px;
    text-align: left;
    transition: all 0.2s;
}

.speed-option:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.speed-option.active {
    color: #7d7aff;
    font-weight: bold;
}

.speed-btn {
    border: 1px solid #666;
    background: transparent;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    min-width: 45px;
}

.speed-btn.btn-active {
    border-color: #7d7aff;
}

</style>
