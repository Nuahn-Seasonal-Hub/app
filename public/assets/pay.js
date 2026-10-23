/* Hosted card entry (Stripe Payment Element). Card details are typed into Stripe's own iframe and go straight to
 * Stripe; this script only receives a client secret and a publishable key from data attributes. */
(function () {
  var root = document.getElementById('pay-root');
  if (!root || !window.Stripe) { return; }
  var stripe = window.Stripe(root.dataset.pk);
  var elements = stripe.elements({ clientSecret: root.dataset.secret });
  var el = elements.create('payment');
  el.mount('#pay-element');
  var form = document.getElementById('pay-form'), btn = document.getElementById('pay-btn'), msg = document.getElementById('pay-msg');
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    btn.disabled = true; msg.hidden = true;
    stripe.confirmPayment({ elements: elements, redirect: 'if_required', confirmParams: { return_url: root.dataset.ret } }).then(function (r) {
      if (r.error) { msg.textContent = r.error.message || 'The payment could not be authorised.'; msg.hidden = false; btn.disabled = false; return; }
      window.location.href = root.dataset.ret;
    });
  });
})();
