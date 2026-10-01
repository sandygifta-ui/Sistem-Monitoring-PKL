/**
 * validasi.js — Validasi form real-time
 * Cara pakai: tambahkan attribute di input HTML:
 *   data-validasi="required"         → tidak boleh kosong
 *   data-validasi="required|min:6"   → wajib diisi, minimal 6 karakter
 *   data-validasi="required|match:id_field_lain" → harus sama dengan field lain
 *   data-label="Nama Field"          → nama yang ditampilkan di pesan error
 */

(function () {

  // Pesan error per aturan
  const pesan = {
    required : (label) => `${label} wajib diisi.`,
    min      : (label, val) => `${label} minimal ${val} karakter.`,
    max      : (label, val) => `${label} maksimal ${val} karakter.`,
    match    : (label) => `${label} tidak cocok.`,
    number   : (label) => `${label} harus berupa angka.`,
    minval   : (label, val) => `${label} minimal ${val}.`,
    maxval   : (label, val) => `${label} maksimal ${val}.`,
  };

  // Tampilkan status valid / invalid di bawah field
  function setStatus(input, valid, msg) {
    // Hapus pesan lama
    const old = input.parentElement.querySelector('.validasi-msg');
    if (old) old.remove();

    if (valid) {
      input.classList.remove('is-invalid');
      input.classList.add('is-valid');
    } else {
      input.classList.remove('is-valid');
      input.classList.add('is-invalid');

      // Buat pesan error kecil
      const div = document.createElement('div');
      div.className = 'validasi-msg invalid-feedback d-block';
      div.style.fontSize = '0.78rem';
      div.innerHTML = '⚠ ' + msg;
      input.parentElement.appendChild(div);
    }
  }

  // Reset status
  function resetStatus(input) {
    input.classList.remove('is-valid', 'is-invalid');
    const old = input.parentElement.querySelector('.validasi-msg');
    if (old) old.remove();
  }

  // Validasi satu input
  function validasiInput(input) {
    const rules = (input.dataset.validasi || '').split('|').filter(Boolean);
    const label = input.dataset.label || input.name || 'Field ini';
    const val   = input.value.trim();

    for (const rule of rules) {
      const [nama, arg] = rule.split(':');

      if (nama === 'required' && val === '') {
        setStatus(input, false, pesan.required(label));
        return false;
      }

      if (nama === 'min' && val.length < parseInt(arg)) {
        setStatus(input, false, pesan.min(label, arg));
        return false;
      }

      if (nama === 'max' && val.length > parseInt(arg)) {
        setStatus(input, false, pesan.max(label, arg));
        return false;
      }

      if (nama === 'number' && isNaN(val)) {
        setStatus(input, false, pesan.number(label));
        return false;
      }

      if (nama === 'minval' && parseFloat(val) < parseFloat(arg)) {
        setStatus(input, false, pesan.minval(label, arg));
        return false;
      }

      if (nama === 'maxval' && parseFloat(val) > parseFloat(arg)) {
        setStatus(input, false, pesan.maxval(label, arg));
        return false;
      }

      if (nama === 'match') {
        const target = document.getElementById(arg);
        if (target && val !== target.value.trim()) {
          setStatus(input, false, pesan.match(label));
          return false;
        }
      }
    }

    // Semua valid
    if (rules.length > 0 && val !== '') {
      setStatus(input, true, '');
    }
    return true;
  }

  // Pasang event listener ke semua input dengan data-validasi
  function initValidasi() {
    const inputs = document.querySelectorAll('[data-validasi]');

    inputs.forEach(function (input) {
      // Saat user keluar dari field (blur)
      input.addEventListener('blur', function () {
        validasiInput(this);
      });

      // Saat user mulai mengetik ulang — reset dulu
      input.addEventListener('input', function () {
        resetStatus(this);
      });
    });

    // Validasi semua saat submit
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        let valid = true;
        form.querySelectorAll('[data-validasi]').forEach(function (input) {
          if (!validasiInput(input)) valid = false;
        });
        if (!valid) e.preventDefault();
      });
    });
  }

  // Jalankan setelah DOM siap
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initValidasi);
  } else {
    initValidasi();
  }

})();
