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


loginBtn.addEventListener("click", async () => {
  const email = document.getElementById("login-email").value.trim();
  const password = passwordInput.value.trim();

  loginBtn.disabled = true;
  loginBtn.textContent = "Signing in...";

  await new Promise(resolve => setTimeout(resolve, 1000));

  if (email === "" || password === "") {
    Swal.fire({
      icon: "error",
      title: "Missing Fields",
      text: "Please enter both email and password.",
    }).then(() => {
      loginBtn.disabled = false;
      loginBtn.textContent = "Sign in";
    });
    return;
  }

  // for the eme login
  if (email === "admin@deped.gov" && password === "admin123") {
    Swal.fire({
      icon: "success",
      title: "Login Successful",
      text: "Welcome back!",
    }).then(() => {
      window.location.href = "/dashboard";
    });
  } else {
    Swal.fire({
      icon: "error",
      title: "Login Failed",
      text: "Invalid email or password.",
    }).then(() => {
      loginBtn.disabled = false;
      loginBtn.textContent = "Sign in";
    });
  }
});
