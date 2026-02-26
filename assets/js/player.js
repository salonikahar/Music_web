/* =========================================================
   SINGLE SOURCE OF TRUTH & ELEMENTS
========================================================= */
let currentSong = null;
let isPlaying = false;
let currentPlaylist = [];
let preMuteVolume = 1;
let isShuffle = false;
let isRepeat = false;
let youtubePlayer = null;

// Audio & Core Controls
const audio = document.getElementById("audio-player");
const playerBar = document.getElementById("playerBar");
const playBtn = document.getElementById("play-pause-btn");
const prevBtn = document.getElementById("prev-btn");
const nextBtn = document.getElementById("next-btn");

// Now Playing Info
const nowPlayingImage = document.getElementById("now-playing-image");
const nowPlayingTitle = document.getElementById("now-playing-title");
const nowPlayingArtist = document.getElementById("now-playing-artist");

// Progress Bar
const progressRange = document.getElementById("progress-range");
const progressFill = document.getElementById("progress-fill");
const currentTimeEl = document.getElementById("current-time");
const totalTimeEl = document.getElementById("total-time");

// Volume Control
const volumeIconBtn = document.getElementById("volume-icon-btn");
const volumeSlider = document.getElementById("volume-slider");
const volumeFill = document.getElementById("volume-fill");

// YouTube Player Container
let youtubeContainer = null;

/* =========================================================
   HELPERS
========================================================= */
function resolvePath(path) {
    if (!path) return "";
    return path.startsWith("http") ? path : BASE_URL + path;
}

function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return "0:00";
    const min = Math.floor(seconds / 60);
    const sec = Math.floor(seconds % 60);
    return `${min}:${sec < 10 ? "0" : ""}${sec}`;
}

/* =========================================================
   YOUTUBE PLAYER INTEGRATION
========================================================= */
function initYouTubePlayer() {
    if (window.YT && window.YT.Player) {
        return;
    }

    // Load YouTube IFrame API
    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
}

function onYouTubeIframeAPIReady() {
    // YouTube API is ready
}

function createYouTubePlayer(videoId) {
    if (youtubePlayer) {
        youtubePlayer.destroy();
    }

    // Create container for YouTube player
    if (!youtubeContainer) {
        youtubeContainer = document.createElement('div');
        youtubeContainer.id = 'youtube-player-container';
        youtubeContainer.style.display = 'none';
        document.body.appendChild(youtubeContainer);
    }

    youtubePlayer = new YT.Player('youtube-player-container', {
        height: '0',
        width: '0',
        videoId: videoId,
        playerVars: {
            'playsinline': 1,
            'controls': 0,
            'disablekb': 1,
            'fs': 0,
            'iv_load_policy': 3,
            'modestbranding': 1,
            'rel': 0
        },
        events: {
            'onReady': onYouTubePlayerReady,
            'onStateChange': onYouTubePlayerStateChange
        }
    });
}

function onYouTubePlayerReady(event) {
    // Set volume
    event.target.setVolume(audio.volume * 100);
    if (isPlaying) {
        event.target.playVideo();
    }
}

function onYouTubePlayerStateChange(event) {
    if (event.data == YT.PlayerState.ENDED) {
        playNextSong();
    } else if (event.data == YT.PlayerState.PLAYING) {
        isPlaying = true;
        playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
        updateProgressFromYouTube();
    } else if (event.data == YT.PlayerState.PAUSED) {
        isPlaying = false;
        playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
    }
}

function updateProgressFromYouTube() {
    if (!youtubePlayer) return;

    const duration = youtubePlayer.getDuration();
    const currentTime = youtubePlayer.getCurrentTime();

    if (duration && currentTime) {
        totalTimeEl.textContent = formatTime(duration);
        currentTimeEl.textContent = formatTime(currentTime);
        const percent = (currentTime / duration) * 100;
        progressRange.value = percent;
        progressFill.style.width = percent + "%";
    }

    if (isPlaying) {
        setTimeout(updateProgressFromYouTube, 1000);
    }
}

/* =========================================================
   CORE PLAYER ACTIONS
========================================================= */
function loadSong(song) {
    if (!song || !song.file_path) return;

    currentSong = song;

    // Check if it's a YouTube video (from saavn API)
    const isYouTubeVideo = song.file_path && song.file_path.includes('youtube.com/watch');

    if (isYouTubeVideo) {
        // Extract video ID from YouTube URL
        const videoId = song.file_path.split('v=')[1]?.split('&')[0];
        if (videoId) {
            createYouTubePlayer(videoId);
        }
    } else {
        // Regular audio file
        if (youtubePlayer) {
            youtubePlayer.destroy();
            youtubePlayer = null;
        }
        audio.src = resolvePath(song.file_path);
        audio.currentTime = 0;
    }

    nowPlayingTitle.textContent = song.title;
    nowPlayingArtist.textContent = song.artist_name || "Unknown Artist";
    nowPlayingImage.src = song.cover
        ? resolvePath(song.cover)
        : 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==';

    playerBar.classList.add("active");

    // Record play only for LOCAL songs
    if (!song.id?.toString().startsWith("saavn_")) {
        highlightCurrentSong(song.id);
        recordPlay(song.id);
    }

    savePlayerState();

    if (isYouTubeVideo) {
        // YouTube player will handle playback
        if (youtubePlayer && youtubePlayer.playVideo) {
            play();
        }
    } else {
        if (audio.readyState >= 1) {
            play();
        } else {
            audio.addEventListener("loadedmetadata", function onMeta() {
                audio.removeEventListener("loadedmetadata", onMeta);
                play();
            }, { once: true });
        }
    }
}

function play() {
    if (!currentSong) return;
    audio.play();
    isPlaying = true;
    playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
}

function pause() {
    audio.pause();
    isPlaying = false;
    playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
}

/* =========================================================
   EVENT LISTENERS
========================================================= */

// Play / Pause
playBtn.addEventListener("click", () => {
    // Check if it's a YouTube video
    const isYouTubeVideo = currentSong && currentSong.file_path && currentSong.file_path.includes('youtube.com/watch');

    if (isYouTubeVideo && youtubePlayer) {
        if (isPlaying) {
            youtubePlayer.pauseVideo();
        } else {
            youtubePlayer.playVideo();
        }
    } else {
        isPlaying ? pause() : play();
    }
});

// Metadata & Progress
audio.addEventListener("loadedmetadata", () => {
    totalTimeEl.textContent = formatTime(audio.duration);
});

audio.addEventListener("timeupdate", () => {
    if (!audio.duration) return;
    const percent = (audio.currentTime / audio.duration) * 100;
    progressRange.value = percent;
    progressFill.style.width = percent + "%";
    currentTimeEl.textContent = formatTime(audio.currentTime);
    savePlayerState();
});

progressRange.addEventListener("input", () => {
    // Check if it's a YouTube video
    const isYouTubeVideo = currentSong && currentSong.file_path && currentSong.file_path.includes('youtube.com/watch');

    if (isYouTubeVideo && youtubePlayer) {
        const duration = youtubePlayer.getDuration();
        if (duration) {
            const seekTime = (progressRange.value / 100) * duration;
            youtubePlayer.seekTo(seekTime);
        }
    } else {
        if (!audio.duration) return;
        audio.currentTime = (progressRange.value / 100) * audio.duration;
    }
});

// Next / Previous
nextBtn.addEventListener("click", playNextSong);
prevBtn.addEventListener("click", playPrevSong);
audio.addEventListener("ended", playNextSong);

function playNextSong() {
    if (!currentPlaylist.length) return;

    if (currentPlaylist.length === 1) {
        play();
        return;
    }

    let index = currentPlaylist.findIndex(s => s.id === currentSong.id);

    if (isShuffle) {
        index = Math.floor(Math.random() * currentPlaylist.length);
    } else {
        index = (index + 1) % currentPlaylist.length;
        if (!isRepeat && index === 0) return;
    }

    loadSong(currentPlaylist[index]);
}

function playPrevSong() {
    if (!currentPlaylist.length) return;

    let index = currentPlaylist.findIndex(s => s.id === currentSong.id);
    index = (index - 1 + currentPlaylist.length) % currentPlaylist.length;
    loadSong(currentPlaylist[index]);
}

/* =========================================================
   VOLUME
========================================================= */
function setVolume(value) {
    const v = Math.max(0, Math.min(1, value));
    audio.volume = v;
    audio.muted = v === 0;

    // Also set YouTube volume if playing YouTube video
    if (youtubePlayer && currentSong && currentSong.file_path && currentSong.file_path.includes('youtube.com/watch')) {
        youtubePlayer.setVolume(v * 100);
    }

    volumeSlider.value = v;
    volumeFill.style.width = (v * 100) + "%";
    updateVolumeIcon();
}

function updateVolumeIcon() {
    if (audio.muted || audio.volume === 0) {
        volumeIconBtn.innerHTML = '<i class="bi bi-volume-mute-fill"></i>';
    } else if (audio.volume < 0.5) {
        volumeIconBtn.innerHTML = '<i class="bi bi-volume-down-fill"></i>';
    } else {
        volumeIconBtn.innerHTML = '<i class="bi bi-volume-up-fill"></i>';
    }
}

volumeSlider.addEventListener("input", () => {
    setVolume(volumeSlider.value);
    preMuteVolume = audio.volume;
});

volumeIconBtn.addEventListener("click", () => {
    if (audio.volume > 0) {
        preMuteVolume = audio.volume;
        setVolume(0);
    } else {
        setVolume(preMuteVolume || 1);
    }
});

/* =========================================================
   PLAYBACK INITIATION
========================================================= */
function playSong(el) {
    const song = JSON.parse(el.dataset.song);
    song.file_path = song.file_path || song.audio || "";
    currentPlaylist = [song];
    loadSong(song);
}

function playAlbum(shuffle = false) {
    const rows = document.querySelectorAll(".song-row[data-song]");
    if (!rows.length) return;
    let songs = Array.from(rows).map(r => JSON.parse(r.dataset.song));
    if (shuffle) songs.sort(() => Math.random() - 0.5);
    currentPlaylist = songs;
    loadSong(currentPlaylist[0]);
}

function playAlbumSong(el) {
    const song = JSON.parse(el.dataset.song);
    const rows = document.querySelectorAll(".song-row[data-song]");
    currentPlaylist = Array.from(rows).map(r => JSON.parse(r.dataset.song));
    loadSong(song);
}

function playPlaylistSong(el) {
    const song = JSON.parse(el.dataset.song);
    const rows = document.querySelectorAll(".song-row[data-song]");
    currentPlaylist = Array.from(rows).map(r => JSON.parse(r.dataset.song));
    loadSong(song);
}

function playHit(el) {
    const song = JSON.parse(el.dataset.song);
    // Ensure file_path exists (for consistency with playSong)
    song.file_path = song.file_path || song.audio || "";

    // Find parent container to scope the playlist
    const container = el.closest('.horizontal-scroll') || el.closest('.scroll-row') || el.parentElement;

    if (container) {
        const hits = container.querySelectorAll(".hit-card[data-song]");
        currentPlaylist = Array.from(hits).map(h => {
            const s = JSON.parse(h.dataset.song);
            s.file_path = s.file_path || s.audio || "";
            return s;
        });
    } else {
        currentPlaylist = [song];
    }

    loadSong(song);
}

/* =========================================================
   STATE MANAGEMENT
========================================================= */
function savePlayerState() {
    if (!currentSong) return;

    let progress = JSON.parse(localStorage.getItem("song_progress")) || {};
    progress[currentSong.id] = audio.currentTime;
    localStorage.setItem("song_progress", JSON.stringify(progress));

    localStorage.setItem("player_state", JSON.stringify({
        song: currentSong,
        playlist: currentPlaylist,
        volume: audio.volume,
        isPlaying: isPlaying
    }));
}

function restorePlayerState() {
    const saved = localStorage.getItem("player_state");
    if (!saved) return;

    const state = JSON.parse(saved);
    if (!state.song || !state.song.file_path) return;

    currentSong = state.song;
    currentPlaylist = state.playlist || [state.song];

    audio.src = resolvePath(state.song.file_path);

    nowPlayingTitle.textContent = state.song.title;
    nowPlayingArtist.textContent = state.song.artist_name || "Unknown Artist";
    nowPlayingImage.src = state.song.cover
        ? resolvePath(state.song.cover)
        : 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==';

    playerBar.classList.add("active");

    audio.onloadedmetadata = () => {
        setVolume(state.volume ?? 1);
        const progress = JSON.parse(localStorage.getItem("song_progress")) || {};
        audio.currentTime = progress[currentSong.id] || 0;
        if (state.isPlaying) play();
    };
}

document.addEventListener("DOMContentLoaded", function () {
    initYouTubePlayer();
    restorePlayerState();
});

/* =========================================================
   DOWNLOAD INTERCEPTION
========================================================= */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.download-btn') || e.target.closest('.search-item-download');

    if (btn) {
        // If IS_LOGGED_IN is explicitly false, prevent download
        if (typeof IS_LOGGED_IN !== 'undefined' && IS_LOGGED_IN === false) {
            e.preventDefault();
            e.stopPropagation();

            // Allow BASE_URL to default to dot if undefined
            const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '.';

            // Use global notification system
            if (typeof showNotification === 'function') {
                showNotification('info', `Please <a href="${baseUrl}/login.php" style="color:white;text-decoration:underline;font-weight:bold;">login</a> to download songs.`, 5000);
            } else {
                alert('Please login to download songs.');
            }
            return false;
        }
    }
});

/* =========================================================
   SERVER HELPERS
========================================================= */
function recordPlay(songId) {
    fetch(`${BASE_URL}/api/record-play.php?song_id=${songId}`).catch(() => { });
}

function highlightCurrentSong(songId) {
    document.querySelectorAll(".song-row").forEach(r => r.classList.remove("playing"));
    const row = document.querySelector(`.song-row[data-song*='"id":${songId}']`);
    if (row) row.classList.add("playing");
}
