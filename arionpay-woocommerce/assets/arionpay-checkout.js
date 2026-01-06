document.addEventListener('DOMContentLoaded', function () {
  if (!window.ARIONPAY_UI) return;

  var invId = ARIONPAY_UI.invId || '';
  var base = ARIONPAY_UI.base || '';
  if (!invId || !base) return;

  var sel = false;
  var menu = document.getElementById('pdex-menu');
  var btn = document.getElementById('pdex-sel-btn');
  var copyBtn = document.getElementById('pdex-copy-btn');

  // Menu anchoring fix
  if (btn && menu) {
    if (menu.parentElement !== btn) {
      btn.appendChild(menu);
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.toggle('show');
    });

    menu.addEventListener('click', function (e) {
      e.stopPropagation();
    });

    document.addEventListener('click', function (e) {
      if (!btn.contains(e.target)) {
        menu.classList.remove('show');
      }
    });
  }

  // Coin selection
  var options = document.querySelectorAll('.pdex-option');
  options.forEach(function (opt) {
    opt.addEventListener('click', function () {
      if (menu) menu.classList.remove('show');

      var c = this.getAttribute('data-chain');
      var l = this.getAttribute('data-logo');

      var trig = document.getElementById('pdex-trig');
      if (trig) trig.innerHTML = '<img src="' + l + '"> ' + c;

      var prompt = document.getElementById('pdex-prompt');
      var content = document.getElementById('pdex-content');
      var loader = document.getElementById('pdex-loader');

      if (prompt) prompt.style.display = 'none';
      if (content) content.style.display = 'none';
      if (loader) loader.style.display = 'block';

      fetch(base + '/api/public/invoice/' + invId + '/asset', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chain: c, asset: c })
      })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          var finalAddr = d.payAddress || d.address;
          var finalUri = d.paymentUri || d.address || d.payAddress;

          if (finalAddr) {
            document.getElementById('pdex-addr').innerText = finalAddr;
            document.getElementById('pdex-amt').innerText = d.amountCrypto;
            document.getElementById('pdex-cur').innerText = d.asset;
            document.getElementById('pdex-qr').src =
              'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=' +
              encodeURIComponent(finalUri);

            if (loader) loader.style.display = 'none';
            if (content) content.style.display = 'block';
            sel = true;
          }
        })
        .catch(function () {
          alert('Connection Error. Please refresh.');
          if (loader) loader.style.display = 'none';
          if (prompt) prompt.style.display = 'block';
        });
    });
  });

  // Copy address
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var txt = document.getElementById('pdex-addr').innerText;
      if (txt && txt !== '---') {
        navigator.clipboard.writeText(txt);
        alert('Address Copied!');
      }
    });
  }

  // Polling status (kept identical behavior)
  setInterval(function () {
    if (!sel) return;

    fetch(base + '/api/public/invoice/' + invId + '?t=' + Date.now())
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var stat = document.getElementById('pdex-stat');
        if (!stat) return;

        if (d.status === 'paid' || d.status === 'confirmed') {
          stat.innerHTML = '<b style="color:green"><i class="fas fa-check-circle"></i> PAID! Redirecting...</b>';
          setTimeout(function () { location.reload(); }, 2000);
        } else if (d.detected) {
          stat.innerHTML = '<b style="color:orange"><i class="fas fa-sync fa-spin"></i> Detecting Payment...</b>';
        }
      });
  }, 3000);
});
