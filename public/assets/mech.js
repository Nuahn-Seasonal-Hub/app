/* Mobile mechanics request: dependent vehicle selects, media previews, address check, cancel preview. UI only. */
(function () {
  var f = document.querySelector('[data-mr]');
  var v = document.querySelector('[data-vehicle]');
  if (v) {
    var map = JSON.parse(v.getAttribute('data-vehicle')), make = v.querySelector('[data-v="make"]'), model = v.querySelector('[data-v="model"]');
    make.addEventListener('change', function () {
      model.innerHTML = '';
      var o = document.createElement('option'); o.value = ''; o.textContent = make.value ? 'Select' : 'Select make first'; model.appendChild(o);
      (map[make.value] || []).concat(make.value ? ['Other'] : []).forEach(function (m) { var x = document.createElement('option'); x.textContent = m; model.appendChild(x); });
      model.disabled = !make.value;
    });
  }
  if (f) {
    var list = f.querySelector('[data-files]');
    var SPR = (document.querySelector('svg use') || {getAttribute: function () { return ''; }}).getAttribute('href').split('#')[0];
    var icons = { photo: 'camera', audio: 'mic', video: 'camera-video' };
    f.addEventListener('change', function (e) {
      var inp = e.target.closest('[data-media]'); if (!inp) return; var kind = inp.dataset.media;
      list.querySelectorAll('[data-k="' + kind + '"]').forEach(function (n) { n.remove(); });
      Array.prototype.forEach.call(inp.files, function (fl) {
        var li = document.createElement('li'); li.dataset.k = kind;
        if (kind === 'photo') { var im = new Image(); im.alt = ''; im.src = URL.createObjectURL(fl); li.appendChild(im); }
        else { li.insertAdjacentHTML('beforeend', '<svg class="ic" aria-hidden="true"><use href="' + SPR + '#' + icons[kind] + '"/></svg>'); }
        var t = document.createElement('span'); t.textContent = fl.name + ' · ' + Math.max(1, Math.round(fl.size / 1024)) + ' KB'; li.appendChild(t);
        var b = document.createElement('button'); b.type = 'button'; b.className = 'mr-files__x'; b.setAttribute('aria-label', 'Remove ' + fl.name); b.textContent = '×';
        b.addEventListener('click', function () { li.remove(); inp.value = ''; }); li.appendChild(b); list.appendChild(li);
      });
    });
    f.addEventListener('submit', function (e) {
      var a = f.querySelector('input[name$="_address"]'), err = f.querySelector('[data-addr-err]');
      if (a && !a.value.trim()) { e.preventDefault(); err.hidden = false; a.setAttribute('aria-invalid', 'true'); a.focus(); }
    });
    var ai = f.querySelector('input[name$="_address"]'); if (ai) ai.addEventListener('input', function () { f.querySelector('[data-addr-err]').hidden = true; ai.removeAttribute('aria-invalid'); });
  }
  var c = document.querySelector('[data-cancel]');
  if (c) c.addEventListener('click', function () { document.querySelector('[data-cancel-msg]').hidden = false; c.disabled = true; });
})();
