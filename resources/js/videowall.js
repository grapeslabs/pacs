import { createApp } from 'vue';
import VideoWall from './Components/VideoWall.vue';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('videowall');

    if (!container) return;

    const payload = JSON.parse(container.dataset.payload);

    createApp(VideoWall, payload).mount('#videowall');
});
