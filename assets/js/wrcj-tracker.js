document.addEventListener('DOMContentLoaded', function() {
  console.log('🎯 WRCJ Tracker initialized');

  function attachTracker(audio) {
    const trackId = audio.dataset.trackId || 'unknown';
    console.log('🎧 Tracking started for ID', trackId);

    audio.addEventListener('timeupdate', function() {
      const pos = Math.floor(audio.currentTime);
      if (pos % 10 === 0 && pos !== 0) { // her 10 saniyede bir
        console.log('🎧 WRCJ Tracker fired for', trackId, pos, 'seconds');
        fetch(wrcjAjax.url, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'action=wrcj_save_progress&track_id=' + trackId + '&position=' + pos
        }).then(r => r.text()).then(res => console.log('✅ Saved', res));
      }
    });
  }

  document.querySelectorAll('audio').forEach(attachTracker);
});
