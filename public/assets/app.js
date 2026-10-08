/* Nuahn Seasonal Hub — app shell behaviour (deferred, dependency-free). */
(function () {
  'use strict';
  var d = document, root = d.documentElement;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Theme toggle (persists locally, and to the profile when signed in) */
  function setTheme(t) {
    root.setAttribute('data-theme', t);
    root.setAttribute('data-bs-theme', t);
    try { localStorage.setItem('nuahn-theme', t); } catch (_) {}
    var ep = d.body.getAttribute('data-theme-endpoint');
    if (ep && window.fetch) {
      var fd = new FormData(); fd.append('theme', t);
      fetch(ep, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
    }
    var meta = d.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', t === 'dark' ? '#050E28' : '#0A1A3F');
    d.dispatchEvent(new CustomEvent('nuahn:theme', { detail: t }));
  }
  d.addEventListener('click', function (e) {
    var tgl = e.target.closest('[data-theme-toggle]');
    if (tgl) {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      if (d.startViewTransition && !reduce) d.startViewTransition(function () { setTheme(next); });
      else setTheme(next);
      return;
    }
    /* App-bar back button: go back in history when we came from this site */
    var back = e.target.closest('[data-back]');
    if (back && history.length > 1 && d.referrer && d.referrer.indexOf(location.origin) === 0) {
      e.preventDefault(); history.back(); return;
    }
    /* Password reveal */
    var rv = e.target.closest('[data-reveal-for]');
    if (rv) {
      var inp = d.getElementById(rv.getAttribute('data-reveal-for'));
      if (inp) {
        var show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        rv.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        var use = rv.querySelector('use');
        if (use) use.setAttribute('href', use.getAttribute('href').replace(/#.*$/, show ? '#eye-slash' : '#eye'));
      }
      return;
    }
    /* Lazy mini maps inside job cards */
    var mt = e.target.closest('[data-map-toggle]');
    if (mt) {
      var id = mt.getAttribute('data-map-toggle');
      var frame = d.getElementById('mapframe' + id);
      if (!frame) return;
      var open = !frame.classList.contains('is-open');
      frame.classList.toggle('is-open', open);
      mt.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open && window.lazyMap) window.lazyMap('map' + id);
    }
  });

  /* Elevate the top bar once the page scrolls */
  var nav = d.getElementById('topnav');
  if (nav) {
    var ticking = false;
    var onScroll = function () {
      if (ticking) return; ticking = true;
      requestAnimationFrame(function () { nav.classList.toggle('is-scrolled', window.scrollY > 4); ticking = false; });
    };
    window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  }

  /* Reveal-on-scroll */
  var items = d.querySelectorAll('[data-reveal]');
  if (!('IntersectionObserver' in window) || reduce) {
    items.forEach(function (el) { el.classList.add('is-in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });
    items.forEach(function (el) { io.observe(el); });
  }

  /* Count-up numbers */
  d.querySelectorAll('[data-count]').forEach(function (el) {
    var end = parseFloat(el.getAttribute('data-count')) || 0;
    if (reduce || end <= 0) { el.textContent = end.toLocaleString(); return; }
    var start = null, dur = 900;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1), eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(end * eased).toLocaleString();
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });

  /* Toasts and legacy alerts auto-dismiss */
  d.querySelectorAll('.toast').forEach(function (t, i) {
    setTimeout(function () { t.classList.add('is-leaving'); setTimeout(function () { t.remove(); }, 300); }, 4200 + i * 400);
  });

  /* Broken upload images: fall back to the gradient placeholder */
  d.querySelectorAll('img[data-fallback]').forEach(function (img) {
    function fail() { var p = img.parentNode; if (p) p.classList.add('is-broken'); }
    if (img.complete && img.naturalWidth === 0) fail(); else img.addEventListener('error', fail);
  });

  /* Hover/touch prefetch for browsers without Speculation Rules (e.g. Safari) */
  var supportsSR = HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules');
  if (!supportsSR) {
    var done = {};
    var pre = function (e) {
      var a = e.target.closest && e.target.closest('a[href]');
      if (!a || a.hasAttribute('data-no-prefetch') || a.origin !== location.origin) return;
      var u = a.href.split('#')[0];
      if (done[u] || u === location.href.split('#')[0] || /logout|\/actions\//.test(u)) return;
      done[u] = 1;
      var l = d.createElement('link'); l.rel = 'prefetch'; l.href = u; d.head.appendChild(l);
    };
    d.addEventListener('mouseover', pre, { passive: true });
    d.addEventListener('touchstart', pre, { passive: true });
  }

  /* Close the account sheet after choosing an item */
  var menu = d.getElementById('user-menu');
  if (menu && menu.hidePopover) {
    menu.addEventListener('click', function (e) { if (e.target.closest('a')) { try { menu.hidePopover(); } catch (_) {} } });
  }
})();
