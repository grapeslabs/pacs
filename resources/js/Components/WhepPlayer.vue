<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { createWhepSession, closeWhepSession } from '../whep';

const props = defineProps({
    uid: { type: String, required: true },
    muted: { type: Boolean, default: true },
    volume: { type: Number, default: 1 },
    paused: { type: Boolean, default: false },
});

const emit = defineEmits(['ready', 'unsupported', 'timeupdate', 'play', 'pause']);

const RECOVER_GRACE = 2000;
const MAX_RESTARTS = 2;

const videoElement = ref(null);

let session = null;
let restarts = 0;
let recoverTimer = null;
let disposed = false;

const attemptRestart = () => {
    if (disposed || !session) return;

    if (restarts >= MAX_RESTARTS) {
        emit('unsupported');
        return;
    }

    restarts++;

    const previous = session;

    session = null;
    previous.pc.removeEventListener('connectionstatechange', onConnectionChange);
    closeWhepSession(previous);

    start();
};

const onConnectionChange = () => {
    if (disposed || !session) return;

    const state = session.pc.connectionState;

    if (state === 'connected') {
        clearTimeout(recoverTimer);
        recoverTimer = null;
        restarts = 0;

        return;
    }

    if (state === 'disconnected') {
        if (recoverTimer) return;

        recoverTimer = setTimeout(() => {
            recoverTimer = null;
            attemptRestart();
        }, RECOVER_GRACE);

        return;
    }

    if (state === 'failed') {
        clearTimeout(recoverTimer);
        recoverTimer = null;
        attemptRestart();
    }
};

const start = async () => {
    try {
        session = await createWhepSession(props.uid);
    } catch (e) {
        if (!disposed) emit('unsupported');
        return;
    }

    if (disposed) {
        closeWhepSession(session);
        session = null;
        return;
    }

    session.pc.addEventListener('connectionstatechange', onConnectionChange);

    const video = videoElement.value;

    if (!video) return;

    video.srcObject = session.stream;
    video.volume = props.volume;
    video.muted = props.muted;

    if (!props.paused) {
        video.play().catch(() => {});
    }

    emit('ready');
};

const stop = () => {
    clearTimeout(recoverTimer);
    recoverTimer = null;

    if (session) {
        session.pc.removeEventListener('connectionstatechange', onConnectionChange);
        closeWhepSession(session);
        session = null;
    }

    if (videoElement.value) {
        videoElement.value.srcObject = null;
    }
};

const play = () => videoElement.value?.play();
const pause = () => videoElement.value?.pause();
const takeScreenshot = () => {
    const video = videoElement.value;

    if (!video || !video.videoWidth) return null;

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

    return canvas.toDataURL('image/jpeg');
};

watch(() => props.muted, (value) => {
    if (videoElement.value) videoElement.value.muted = value;
});

watch(() => props.volume, (value) => {
    if (videoElement.value) videoElement.value.volume = value;
});

watch(() => props.paused, (value) => {
    if (!videoElement.value) return;

    if (value) videoElement.value.pause();
    else videoElement.value.play().catch(() => {});
});

watch(() => props.uid, () => {
    restarts = 0;
    stop();
    start();
});

onMounted(start);

onBeforeUnmount(() => {
    disposed = true;
    stop();
});

defineExpose({ play, pause, takeScreenshot, videoElement });
</script>

<template>
    <video
        ref="videoElement"
        class="whep-video"
        playsinline
        :muted="muted"
        @timeupdate="emit('timeupdate', videoElement?.currentTime)"
        @play="emit('play')"
        @pause="emit('pause')"
    ></video>
</template>

<style scoped>
.whep-video { width: 100%; height: 100%; object-fit: scale-down; background: #000; display: block; }
</style>
