// Tampilkan / sembunyikan password
document.querySelectorAll('.toggle-pass').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.parentElement.querySelector('input');
    input.type = input.type === 'password' ? 'text' : 'password';
  });
});

// Input OTP: pindah otomatis, backspace, dan paste
const otp = document.querySelector('.otp');
if (otp) {
  const boxes = [...otp.querySelectorAll('.otp-box')];
  const hidden = document.getElementById('otpValue');
  const sync = () => { hidden.value = boxes.map(b => b.value).join(''); };

  boxes[0].focus();
  boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/\D/g, '').slice(-1);
      if (box.value && boxes[i + 1]) boxes[i + 1].focus();
      sync();
    });
    box.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !box.value && boxes[i - 1]) { boxes[i - 1].focus(); boxes[i - 1].value = ''; sync(); }
      if (e.key === 'ArrowLeft' && boxes[i - 1]) boxes[i - 1].focus();
      if (e.key === 'ArrowRight' && boxes[i + 1]) boxes[i + 1].focus();
    });
    box.addEventListener('paste', e => {
      e.preventDefault();
      const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, boxes.length);
      digits.split('').forEach((d, k) => { boxes[k].value = d; });
      boxes[Math.min(digits.length, boxes.length - 1)].focus();
      sync();
    });
  });
  document.getElementById('otpForm').addEventListener('submit', sync);
  document.querySelector('button[form="otpForm"]').addEventListener('click', sync);
}
