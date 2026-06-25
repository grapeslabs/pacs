<style>
    #player { display: flex; }
    #player > * { width: 100%; height: 100% !important; }
    #player .video-player-wrapper { height: 100% !important; }
</style>
<div
    id="player"
    style="width: 100%; height: calc(100vh - 260px); max-height: calc(100vh - 260px); overflow: hidden;"
    data-item="{{ json_encode($item) }}"
></div>

@vite(['resources/js/player.js'])
