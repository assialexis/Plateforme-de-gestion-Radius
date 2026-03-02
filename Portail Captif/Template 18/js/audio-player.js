// Gestionnaire de lecteur audio
(function () {
    function initAudioPlayer() {
        // Créer l'élément audio
        var audio = document.createElement('audio');
        audio.id = 'bgMusic';
        audio.src = 'audio/music.mp3';
        audio.preload = 'auto';
        audio.loop = true;
        document.body.appendChild(audio);

        // Créer le bouton
        var btn = document.createElement('button');
        btn.className = 'audio-btn';
        btn.id = 'audioBtn';
        btn.setAttribute('aria-label', 'Lecture audio');
        btn.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="#fff"><path d="M8 5v14l11-7z"/></svg>';

        // Placer dans .brand-logo (comme le bouton de thème)
        var brandLogo = document.querySelector('.brand-logo');
        if (brandLogo) {
            brandLogo.appendChild(btn);
        } else {
            document.body.appendChild(btn);
        }

        var playIcon = '<svg viewBox="0 0 24 24" width="20" height="20" fill="#fff"><path d="M8 5v14l11-7z"/></svg>';
        var pauseIcon = '<svg viewBox="0 0 24 24" width="20" height="20" fill="#fff"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';

        function setPlaying() {
            btn.classList.add('playing');
            btn.innerHTML = pauseIcon;
        }

        function setPaused() {
            btn.classList.remove('playing');
            btn.innerHTML = playIcon;
        }

        btn.addEventListener('click', function () {
            if (audio.paused) {
                audio.play();
                setPlaying();
            } else {
                audio.pause();
                setPaused();
            }
        });

        // Autoplay si la page le demande
        if (window.audioAutoplay) {
            audio.play().then(function () {
                setPlaying();
            }).catch(function () {
                // Navigateur bloque l'autoplay, le bouton reste en pause
                setPaused();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAudioPlayer);
    } else {
        initAudioPlayer();
    }
})();
