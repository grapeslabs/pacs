function updateStatus(streamId, isOnline) {
    const statusDot = document.getElementById(`status-${streamId}`);
    const videoElem = document.getElementById(`video-${streamId}`);
    const errorBlock = document.getElementById(`error-${streamId}`);

    if (statusDot) {
        statusDot.classList.toggle('online', isOnline);
        statusDot.classList.toggle('offline', !isOnline);
    }

    if (videoElem && errorBlock) {
        if (isOnline) {
            videoElem.style.display = 'block';
            errorBlock.style.display = 'none';
        } else {
            videoElem.style.display = 'none';
            errorBlock.style.display = 'flex';
        }
    }
}

function initCamera(video, url) {
    if (!video) return;
    const streamId = video.id.replace('video-', '');

    if (video.hls) {
        video.hls.destroy();
    }

    const hls = new Hls({
        lowLatencyMode: true,
        liveSyncDurationCount: 1,
        liveMaxLatencyDurationCount: 2,
        enableWorker: true,
        manifestLoadingMaxRetry: Infinity,
        levelLoadingMaxRetry: Infinity,
    });

    video.hls = hls;

    hls.on(Hls.Events.MANIFEST_PARSED, () => {
        updateStatus(streamId, true);
        video.muted = true;
        video.play().catch(() => {});
    });

    hls.on(Hls.Events.ERROR, (event, data) => {
        if (data.fatal) {
            updateStatus(streamId, false);
            setTimeout(() => initCamera(video, video.dataset.url) , 5000);
        }
    });

    hls.loadSource(url);
    hls.attachMedia(video);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('video[data-url]').forEach(video => {
        initCamera(video, video.dataset.url);
    });
});
