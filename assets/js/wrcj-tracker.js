document.addEventListener('DOMContentLoaded', () => {
  const ajaxUrl = window.wrcjAjax?.url;
  const lastSent = new Map();

  const shouldSend = (key, position) => {
    const previous = lastSent.get(key);
    const now = Date.now();

    if (!previous || now - previous.time >= 15000 || Math.abs(position - previous.position) >= 5) {
      lastSent.set(key, { time: now, position });
      return true;
    }

    return false;
  };

  const sendProgress = (payloadKey, data) => {
    if (!ajaxUrl || !shouldSend(payloadKey, Number(data.position))) {
      return;
    }

    console.log('🎧 WRCJ Tracker fired for', data.post_id, data.position, data.type);
    fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'wrcj_save_progress',
        post_id: data.post_id,
        position: data.position,
        type: data.type
      })
    }).catch(() => {
      // Network errors are non-blocking for user experience.
    });
  };

  // === AUDIO TRACKER ===
  document.querySelectorAll('.wrap-audio-player audio[data-track-id]').forEach(audio => {
    const trackId = audio.dataset.trackId;
    if (!trackId) {
      return;
    }

    const storageKey = `wrap_position_${trackId}`;

    const restoreAudioPosition = () => {
      const stored = localStorage.getItem(storageKey);
      const savedTime = stored ? parseFloat(stored) : 0;
      if (savedTime && !Number.isNaN(savedTime)) {
        audio.currentTime = Math.min(savedTime, audio.duration || savedTime);
      }
    };

    if (audio.readyState >= 1) {
      restoreAudioPosition();
    } else {
      audio.addEventListener('loadedmetadata', restoreAudioPosition, { once: true });
    }

    audio.addEventListener('timeupdate', () => {
      const pos = Number(audio.currentTime.toFixed(2));
      localStorage.setItem(storageKey, String(pos));
      sendProgress(`audio:${trackId}`, {
        post_id: trackId,
        position: pos,
        type: 'audio'
      });
    });
  });

  // === PDF TRACKER ===
  document.querySelectorAll('[data-wr-pdf-id]').forEach(pdf => {
    const postId = pdf.dataset.wrPdfId;
    if (!postId) {
      return;
    }

    const storageKey = `wrpdf_position_${postId}`;

    const restorePdfPosition = () => {
      const stored = localStorage.getItem(storageKey);
      const savedPage = stored ? parseInt(stored, 10) : 0;
      if (savedPage > 0 && !Number.isNaN(savedPage)) {
        pdf.dispatchEvent(new CustomEvent('wr:setPage', { detail: { pageNumber: savedPage } }));
      }
    };

    if (document.readyState !== 'loading') {
      restorePdfPosition();
    } else {
      window.addEventListener('load', restorePdfPosition, { once: true });
    }

    pdf.addEventListener('pagechange', e => {
      const currentPage = e.detail?.pageNumber || 1;
      localStorage.setItem(storageKey, String(currentPage));
      sendProgress(`pdf:${postId}`, {
        post_id: postId,
        position: currentPage,
        type: 'pdf'
      });
    });
  });
});
