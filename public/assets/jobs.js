/* Open jobs board behaviour (accept, counter-bid, skip). UI only: nothing is saved.
   Sir Paul: Accept -> POST /api/jobs/{id}/accept (roadside: first accepted wins, 409 if taken);
             Counter-bid -> POST /api/tasks/{id}/offers {price, eta, note}. */
(function () {
  function money(n) { return '$' + n.toFixed(2); }
  function upd(card) {
    var inp = card.querySelector('[data-oj-price]'); if (!inp) return;
    var v = parseFloat(inp.value) || 0, pct = +card.dataset.pct, b = +card.dataset.budget;
    card.querySelector('[data-oj-cpay]').textContent = money(v * (100 - pct) / 100);
    var d = v - b, el = card.querySelector('[data-oj-vs]');
    el.textContent = d === 0 ? 'matches the client target' : (d > 0 ? '$' + d + ' above the client target: may be less likely to win' : '$' + (-d) + ' below the client target: more likely to win');
  }
  function res(card, text, cls) { var r = card.querySelector('[data-oj-res]'); r.textContent = text; r.hidden = false; r.className = 'oj__res' + (cls ? ' ' + cls : ''); card.classList.add('is-done'); card.querySelectorAll('.oj__acts button,.oj__acts a').forEach(function (b) { b.disabled = true; }); var f = card.querySelector('[data-oj-form]'); if (f) f.hidden = true; }
  document.addEventListener('click', function (e) {
    var card = e.target.closest('[data-oj]'); if (!card) return;
    var t = e.target.closest('button'); if (!t) return;
    if (t.hasAttribute('data-oj-accept')) {
      res(card, card.dataset.kind === 'roadside' ? 'Accepted. You are the first: the client sees you are on the way. Open Today for directions.' : 'Accepted at $' + card.dataset.budget + '. Waiting for the client to confirm; funds are held once they do.', 'is-ok');
    } else if (t.hasAttribute('data-oj-counter')) {
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
  document.addEventListener('submit', function (e) {
    var f = e.target.closest('[data-oj-form]'); if (!f) return; e.preventDefault();
    var c = f.closest('[data-oj]'); res(c, 'Offer of $' + (parseFloat(c.querySelector('[data-oj-price]').value) || 0) + ' sent. The client compares offers and may accept, message you or wait for others.', 'is-ok');
  });
})();
