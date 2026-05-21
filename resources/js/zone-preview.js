import { createApp } from 'vue';
import VideoPlayer from './Components/VideoPlayer.vue';

window.mountVideoPlayer = function (containerElement) {
    if (!containerElement) return null;

    const itemData = JSON.parse(containerElement.dataset.item ?? 'null');
    if (!itemData?.uid) return null;

    const app = createApp(VideoPlayer, {
        src: `/media/${itemData.uid}/index.m3u8`,
        autoplay: true,
        muted: true,
        onDurationchange() {
            const video = instance.videoElement;
            if (video) {
                containerElement.dispatchEvent(new CustomEvent('zone-preview:ready', {
                    bubbles: true,
                    detail: { width: video.videoWidth, height: video.videoHeight },
                }));
            }
        },
    });

    const instance = app.mount(containerElement);
    return app;
};
