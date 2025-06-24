document.addEventListener('DOMContentLoaded', () => {
    const sendBtn = document.getElementById('sendEmailBtn');
    const emailInput = document.getElementById('forgot-email');
  
    sendBtn.addEventListener('click', () => {
      const email = emailInput.value.trim();
  
      if (!email) {
        Swal.fire({
          icon: 'warning',
          title: 'Email Required',
          text: 'Please enter your email address before proceeding.',
          background: '#1e1e1e',
          color: '#fff',
          confirmButtonColor: '#3C93F8'
        });
        return;
      }
  
      sendBtn.disabled = true;
      sendBtn.textContent = "Sending...";
  
      setTimeout(() => {
        Swal.fire({
          icon: 'success',
          title: 'Email Sent!',
          text: `A reset code has been sent to: ${email}`,
          background: '#1e1e1e',
          color: '#fff',
          confirmButtonColor: '#3C93F8'
        }).then(() => {
          sendBtn.disabled = false;
          sendBtn.textContent = "Send Again";
          window.location.href = "/code"; 
        });
      }, 1500);
    });
  });
  