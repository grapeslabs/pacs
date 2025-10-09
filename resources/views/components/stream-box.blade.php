@php
    if(isset($item)) { $stream = $item; }
    $is_stoped = Cache::get('drive_limit_stoped', false)
@endphp
<div class="camera-card">
    <div class="camera-actions">
        <a class="icon-btn" title="Редактировать" href="{{route('moonshine.resource.page',['video-stream-resource','form-page', $stream->id])}}">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pen" viewBox="0 0 16 16">
                <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001m-.644.766a.5.5 0 0 0-.707 0L1.95 11.756l-.764 3.057 3.057-.764L14.44 3.854a.5.5 0 0 0 0-.708z"/>
            </svg>
        </a>
        <a class="icon-btn" title="На весь экран" href="{{route('moonshine.resource.page',['video-stream-resource','stream-player', $stream->id])}}" {{$stream->is_active?'':'hidden="hidden"'}}>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-fullscreen" viewBox="0 0 16 16">
                <path d="M1.5 1a.5.5 0 0 0-.5.5v4a.5.5 0 0 1-1 0v-4A1.5 1.5 0 0 1 1.5 0h4a.5.5 0 0 1 0 1zM10 .5a.5.5 0 0 1 .5-.5h4A1.5 1.5 0 0 1 16 1.5v4a.5.5 0 0 1-1 0v-4a.5.5 0 0 0-.5-.5h-4a.5.5 0 0 1-.5-.5M.5 10a.5.5 0 0 1 .5.5v4a.5.5 0 0 0 .5.5h4a.5.5 0 0 1 0 1h-4A1.5 1.5 0 0 1 0 14.5v-4a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a.5.5 0 0 1 0-1h4a.5.5 0 0 0 .5-.5v-4a.5.5 0 0 1 .5-.5"/>
            </svg>
        </a>
    </div>

    <div class="camera-container" id="container-{{ $stream->id }}">
        @if($stream->is_active && !$is_stoped)
            <video id="video-{{ $stream->id }}" data-url="/media/api/v1/stream/{{ $stream->uid }}/live/index.m3u8" class="camera-video"></video>
            <div id="error-{{ $stream->id }}" class="camera-error" style="display: none;">
                <div class="camera-error-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="128px" height="128px" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24">
                        <path fill="white" d="M12.193,10h5.807c1.302,0,2.402-.839,2.816-2h1.184c.552,0,1-.448,1-1v-3c0-.552-.448-1-1-1h-.382l.776-1.553c.155-.31,.138-.678-.044-.973s-.504-.475-.851-.475H5.5c-1.933,0-3.5,1.567-3.5,3.5v3c0,1.933,1.567,3.5,3.5,3.5h4.557l-1.52,4.054c-.439,1.171-1.558,1.946-2.808,1.946H2v-3c0-.553-.448-1-1-1s-1,.447-1,1v8c0,.553,.448,1,1,1s1-.447,1-1v-3h3.728c2.084,0,3.95-1.293,4.682-3.244l1.784-4.756Zm11.563,11.337l-5.197-8.458c-.683-1.171-2.376-1.171-3.059,0l-5.256,8.458c-.689,1.181,.163,2.663,1.53,2.663h10.453c1.367,0,2.218-1.483,1.53-2.663Zm-6.756,1.663c-.552,0-1-.448-1-1s.448-1,1-1,1,.448,1,1-.448,1-1,1Zm1-4c0,.552-.448,1-1,1s-1-.448-1-1v-3c0-.552,.448-1,1-1s1,.448,1,1v3Z"/>
                    </svg>
                </div>
                <div class="camera-error-text">Потеряно соединение</div>
            </div>
        @else
            <div class="camera-error">
                <div class="camera-error-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="128px" height="128px" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24">
                        <path fill="white" d="M12.193,10h5.807c1.302,0,2.402-.839,2.816-2h1.184c.552,0,1-.448,1-1v-3c0-.552-.448-1-1-1h-.382l.776-1.553c.155-.31,.138-.678-.044-.973s-.504-.475-.851-.475H5.5c-1.933,0-3.5,1.567-3.5,3.5v3c0,1.933,1.567,3.5,3.5,3.5h4.557l-1.52,4.054c-.439,1.171-1.558,1.946-2.808,1.946H2v-3c0-.553-.448-1-1-1s-1,.447-1,1v8c0,.553,.448,1,1,1s1-.447,1-1v-3h3.728c2.084,0,3.95-1.293,4.682-3.244l1.784-4.756Zm11.563,11.337l-5.197-8.458c-.683-1.171-2.376-1.171-3.059,0l-5.256,8.458c-.689,1.181,.163,2.663,1.53,2.663h10.453c1.367,0,2.218-1.483,1.53-2.663Zm-6.756,1.663c-.552,0-1-.448-1-1s.448-1,1-1,1,.448,1,1-.448,1-1,1Zm1-4c0,.552-.448,1-1,1s-1-.448-1-1v-3c0-.552,.448-1,1-1s1,.448,1,1v3Z"/>
                    </svg>
                </div>
                <div class="camera-error-text">Камера отключена{{$is_stoped?' из-за переполнения диска':null}}</div>
            </div>
        @endif
    </div>

    <div class="camera-footer">
        <span id="status-{{ $stream->id }}" class="status-dot offline"></span>
        <span class="camera-title">
            {{ $stream->name }} | {{ $stream->location ?? 'Неизвестно' }}
        </span>
    </div>
</div>
