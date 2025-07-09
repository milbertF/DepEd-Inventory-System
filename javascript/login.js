const loginBtn = document.getElementById("login-btn");
const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("login-password");


togglePassword.style.display = "none";

passwordInput.addEventListener("input", () => {
  togglePassword.style.display = passwordInput.value ? "block" : "none";
});


togglePassword.addEventListener("click", () => {
  const isPassword = passwordInput.type === "password";
  passwordInput.type = isPassword ? "text" : "password";
  togglePassword.classList.toggle("fa-eye");
  togglePassword.classList.toggle("fa-eye-slash");
});



document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm");
  const loginBtn = document.getElementById("login-btn");

  loginForm.addEventListener("submit", (e) => {
    loginBtn.disabled = true;
    loginBtn.textContent = "Logging In...";
  });
});


