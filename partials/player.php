 


<div class="now-playing-bar" id="playerBar">
    <div class="now-playing-info">
        <img id="now-playing-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==">
        <div>
            <div id="now-playing-title">Select a song</div>
            <div id="now-playing-artist"></div>
        </div>
    </div>

    <div class="player-center">
        <div class="player-controls">
            <button id="prev-btn" title="Previous"><i class="bi bi-skip-backward-fill"></i></button>
            <button id="play-pause-btn" title="Play/Pause"><i class="bi bi-play-fill"></i></button>
            <button id="next-btn" title="Next"><i class="bi bi-skip-forward-fill"></i></button>
        </div>
        <div class="progress-container">
            <span id="current-time" class="time-display">0:00</span>
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
                <input type="range" id="progress-range" min="0" max="100" value="0" step="0.1">
            </div>
            <span id="total-time" class="time-display">0:00</span>
        </div>
    </div>

    <div class="player-right">
        <div class="volume-control">
            <button id="volume-icon-btn"><i class="bi bi-volume-up-fill"></i></button>
            <div class="volume-bar">
                <div class="volume-fill" id="volume-fill"></div>
                <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="1">
            </div>
        </div>
    </div>

    <audio id="audio-player"></audio>
</div>



