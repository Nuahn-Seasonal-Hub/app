/* Property / location picker (self-contained, no external scripts).
 * HOOK FOR GOOGLE MAPS OR MAPBOX: set window.NuahnMapProvider before this file loads (see nu_map_picker() in includes/ui.php):
 *   { mount(el, {lat, lng, onMove}) -> {setPosition(lat, lng)}, geocode(text) -> Promise<{lat, lng, label}>, reverse(lat, lng) -> Promise<string> }
 * Without a provider the built-in sample map below is used. Sample coordinates only; nothing is sent anywhere. */
(function () {
  'use strict';
  var BOX = { n: 53.53, s: 53.45, w: -113.60, e: -113.45 };      // sample bounds of the static map
  function toPct(lat, lng) { return { x: (lng - BOX.w) / (BOX.e - BOX.w) * 100, y: (BOX.n - lat) / (BOX.n - BOX.s) * 100 }; }
  function toLL(x, y) { return { lat: BOX.n - y / 100 * (BOX.n - BOX.s), lng: BOX.w + x / 100 * (BOX.e - BOX.w) }; }
  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
  function hash(s) { var h = 7; for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) % 1000003; return h; }
  var P = window.NuahnMapProvider || null;

  document.querySelectorAll('[data-map-picker]').forEach(function (root) {
    var map = root.querySelector('[data-mp-map]'), pin = root.querySelector('[data-mp-pin]');
    var addr = root.querySelector('[data-mp-addr]'), msg = root.querySelector('[data-mp-text]');
    var iLat = root.querySelector('[data-mp-lat]'), iLng = root.querySelector('[data-mp-lng]');
    var lat = parseFloat(root.dataset.lat), lng = parseFloat(root.dataset.lng), api = null, dragging = false;

    function emit() { root.dispatchEvent(new CustomEvent('mp:change', { bubbles: true, detail: { lat: lat, lng: lng, address: addr.value } })); }
    function place(la, ln, quiet) {
      lat = la; lng = ln; iLat.value = la.toFixed(5); iLng.value = ln.toFixed(5);
      var p = toPct(la, ln); pin.style.left = clamp(p.x, 3, 97) + '%'; pin.style.top = clamp(p.y, 6, 97) + '%';
      if (api && api.setPosition) api.setPosition(la, ln);
      if (!quiet) emit();
    }
    function settle() {
      msg.textContent = 'Pin set at ' + lat.toFixed(4) + ', ' + lng.toFixed(4) + '.';
      if (P && P.reverse) P.reverse(lat, lng).then(function (t) { if (t) { addr.value = t; emit(); } });
    }
    if (P && P.mount) { api = P.mount(map, { lat: lat, lng: lng, onMove: function (a, b) { place(a, b); settle(); } }); }
    place(lat, lng, true);

    pin.addEventListener('pointerdown', function (e) { dragging = true; pin.classList.add('is-drag'); pin.setPointerCapture(e.pointerId); e.preventDefault(); });
    pin.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      var r = map.getBoundingClientRect(), x = clamp((e.clientX - r.left) / r.width * 100, 3, 97), y = clamp((e.clientY - r.top) / r.height * 100, 6, 97), ll = toLL(x, y);
      place(ll.lat, ll.lng);
    });
    function end() { if (!dragging) return; dragging = false; pin.classList.remove('is-drag'); settle(); }
    pin.addEventListener('pointerup', end); pin.addEventListener('pointercancel', end);
    map.addEventListener('click', function (e) {              // tap the map to drop the pin there
      if (e.target.closest('[data-mp-pin],[data-mp-locate]') || (P && P.mount)) return;
      var r = map.getBoundingClientRect(), ll = toLL(clamp((e.clientX - r.left) / r.width * 100, 3, 97), clamp((e.clientY - r.top) / r.height * 100, 6, 97));
      place(ll.lat, ll.lng); settle();
    });
    pin.addEventListener('keydown', function (e) {
      var d = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] }[e.key]; if (!d) return;
      e.preventDefault(); var p = toPct(lat, lng), ll = toLL(clamp(p.x + d[0] * 2, 3, 97), clamp(p.y + d[1] * 2, 6, 97)); place(ll.lat, ll.lng); settle();
    });

    function find() {
      var t = addr.value.trim(); if (!t) { msg.textContent = 'Type an address first.'; addr.focus(); return; }
      if (P && P.geocode) { P.geocode(t).then(function (r) { if (r) { place(r.lat, r.lng); if (r.label) addr.value = r.label; msg.textContent = 'Address found.'; } }); return; }
      var h = hash(t.toLowerCase()), ll = toLL(30 + (h % 4000) / 100, 25 + (Math.floor(h / 4000) % 4000) / 100 * 1.2);
      place(ll.lat, ll.lng); msg.textContent = 'Address found (sample map). Fine-tune the pin if needed.';
    }
    root.querySelector('[data-mp-find]').addEventListener('click', find);
    addr.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); find(); } });
    addr.addEventListener('change', emit);

    root.querySelector('[data-mp-locate]').addEventListener('click', function () {
      msg.textContent = 'Finding your location…';
      var done = function (la, ln, note) { place(la, ln); msg.textContent = note; if (!addr.value) addr.value = 'Near my current location'; emit(); };
      if (!navigator.geolocation) { done(53.4891, -113.5262, 'Location is not available here. Pin placed at the sample address.'); return; }
      navigator.geolocation.getCurrentPosition(function (pos) {
        var la = pos.coords.latitude, ln = pos.coords.longitude;
        if (la < BOX.s || la > BOX.n || ln < BOX.w || ln > BOX.e) { done(53.4891, -113.5262, 'Found you. The sample map only covers Edmonton, so the pin is at the sample address.'); }
        else done(la, ln, 'Pin moved to your location. Drag it to fine-tune.');
      }, function () { done(53.4891, -113.5262, 'Location permission was not given. Type an address or drag the pin instead.'); }, { timeout: 6000, maximumAge: 60000 });
    });
  });
}());
