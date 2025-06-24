document.addEventListener('DOMContentLoaded', () => {
  const inputs = document.querySelectorAll('.inpAuth input');
  const confirmBtn = document.getElementById('confirm');
  const resendBtn = document.getElementById('resendBtn');


  const updateConfirmState = () => {
    const allFilled = Array.from(inputs).every(input => input.value.trim() !== '');
    confirmBtn.disabled = !allFilled;
  };

  inputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
      const value = e.target.value;

      if (value.length === 1 && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }

      if (value.length > 1) {
        const digits = value.replace(/\D/g, '').split('');
        inputs[index].value = digits[0];
        for (let i = 1; i < digits.length && index + i < inputs.length; i++) {
          inputs[index + i].value = digits[i];
        }
        const nextInput = inputs[Math.min(index + digits.length, inputs.length - 1)];
        nextInput.focus();
      }

      updateConfirmState();
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace') {
        if (input.value === '' && index > 0) {
          inputs[index - 1].focus();
          inputs[index - 1].value = '';
          e.preventDefault();
        }
      } else if (e.key === 'ArrowLeft' && index > 0) {
        inputs[index - 1].focus();
      } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });

    input.addEventListener('paste', (e) => {
      const paste = e.clipboardData.getData('text').replace(/\D/g, '');
      const digits = paste.split('');
      digits.forEach((digit, i) => {
        if (index + i < inputs.length) {
          inputs[index + i].value = digit;
        }
      });
      const lastFilled = inputs[Math.min(index + digits.length, inputs.length - 1)];
      lastFilled.focus();
      e.preventDefault();
      updateConfirmState();
    });

    input.addEventListener('focus', () => {
      input.select();
    });
  });


  confirmBtn.addEventListener('click', () => {
    const code = Array.from(inputs).map(i => i.value.trim()).join('');
    if (code.length !== 6) return;

    Swal.fire({
      icon: 'success',
      title: 'Code Verified!',
      text: 'Redirecting you to reset your password...',
      timer: 1500,
      showConfirmButton: false
    }).then(() => {
      window.location.href = '/reset';
    });
  });


  resendBtn.addEventListener('click', () => {
    resendBtn.disabled = true;
    resendBtn.textContent = "Resending...";

    setTimeout(() => {
      Swal.fire({
        icon: 'info',
        title: 'Code Resent',
        text: "A new code has been sent to your email."
      }).then(() => {
        resendBtn.disabled = false;
        resendBtn.textContent = "Resend Code";
      });
    }, 1000);
  });
});
