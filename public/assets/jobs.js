/* Open jobs board helpers: counter-bid panel, price stepper, "not for me" hide (local only).
   Offers, acceptance and withdrawals are real form posts to actions/marketplace.php. */
(function () {
  function money(n) { return '$' + n.toFixed(2); }
  function upd(card) {
    var inp = card.querySelector('[data-oj-price]'); if (!inp) return;
    var v = parseFloat(inp.value) || 0, pct = +card.dataset.pct, b = +card.dataset.budget;
    card.querySelector('[data-oj-cpay]').textContent = money(v * (100 - pct) / 100);
    var d = v - b, el = card.querySelector('[data-oj-vs]');
    el.textContent = d === 0 ? 'matches the client target' : (d > 0 ? '$' + d + ' above the client target: may be less likely to win' : '$' + (-d) + ' below the client target: more likely to win');
  }
  document.addEventListener('click', function (e) {
    var card = e.target.closest('[data-oj]'); if (!card) return;
    var t = e.target.closest('button'); if (!t) return;
    if (t.hasAttribute('data-oj-counter')) {
      var f = card.querySelector('[data-oj-form]'); f.hidden = !f.hidden; t.setAttribute('aria-expanded', String(!f.hidden)); upd(card);
    } else if (t.hasAttribute('data-oj-cancel')) {
      card.querySelector('[data-oj-form]').hidden = true; card.querySelector('[data-oj-counter]').setAttribute('aria-expanded', 'false');
    } else if (t.hasAttribute('data-oj-step')) {
      var i = card.querySelector('[data-oj-price]'); i.value = Math.max(10, (parseFloat(i.value) || 0) + (+t.dataset.ojStep)); upd(card);
    } else if (t.hasAttribute('data-oj-skip')) {
      card.classList.add('is-skipped'); card.hidden = true; document.dispatchEvent(new CustomEvent('oj:skip'));
    }
  });
  document.addEventListener('input', function (e) { if (e.target.matches('[data-oj-price]')) upd(e.target.closest('[data-oj]')); });
})();
