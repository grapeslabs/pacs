import { createApp } from 'vue';
import VideoPlayer from './Components/CameraViewer.vue';

document.addEventListener('DOMContentLoaded', () => {
    const playerContainer = document.getElementById('stream-player');

    if (playerContainer) {
        const rawData = playerContainer.dataset.item;
        const itemData = JSON.parse(rawData);
        const app = createApp(VideoPlayer, {
            camera: itemData
        });

        app.mount('#stream-player');
    }
});
