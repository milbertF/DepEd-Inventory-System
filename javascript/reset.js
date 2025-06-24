const resetBtn = document.getElementById("reset-btn");
const newPasswordInput = document.getElementById("new-password");
const confirmPasswordInput = document.getElementById("confirm-password");
const toggleNew = document.getElementById("toggleNewPassword");
const toggleConfirm = document.getElementById("toggleConfirmPassword");


resetBtn.addEventListener("click", () => {
  const newPassword = newPasswordInput.value.trim();
  const confirmPassword = confirmPasswordInput.value.trim();

  if (!newPassword || !confirmPassword) {
    return Swal.fire({
      icon: "error",
      title: "Missing Fields",
      text: "Please enter and confirm your new password.",
      background: "#1e1e1e",
      color: "#fff",
      confirmButtonColor: "#3C93F8",
    });
  }

  if (newPassword !== confirmPassword) {
    return Swal.fire({
      icon: "error",
      title: "Passwords Do Not Match",
      text: "Make sure both passwords are the same.",
      background: "#1e1e1e",
      color: "#fff",
      confirmButtonColor: "#3C93F8",
    });
  }


  Swal.fire({
    icon: "success",
    title: "Password Reset",
    text: "Your password has been updated successfully.",
    background: "#1e1e1e",
    color: "#fff",
    confirmButtonColor: "#3C93F8",
  }).then(() => {
    window.location.href = "login";
  });
});


function handleToggle(input, toggleIcon) {
  toggleIcon.addEventListener("click", () => {
    const isHidden = input.type === "password";
    input.type = isHidden ? "text" : "password";
    toggleIcon.classList.toggle("fa-eye");
    toggleIcon.classList.toggle("fa-eye-slash");
  });

  input.addEventListener("input", () => {
    if (input.value.trim()) {
      toggleIcon.style.display = "block";
    } else {
      toggleIcon.style.display = "none";
      input.type = "password";
      toggleIcon.classList.add("fa-eye-slash");
      toggleIcon.classList.remove("fa-eye");
    }
  });
}


handleToggle(newPasswordInput, toggleNew);
handleToggle(confirmPasswordInput, toggleConfirm);
