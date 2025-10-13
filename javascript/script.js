function redirect(page) {
    window.location.href = page;
}

function showSidebarAccountTooltip() {
    const sidebarAccountTooltip = document.getElementById('sidebarAccountTooltip');

    if (sidebarAccountTooltip.style.display === "none") {
        sidebarAccountTooltip.style.display = "flex";
    } else {
        sidebarAccountTooltip.style.display = "none";
    }
}

const existingicon = document.querySelector("link[rel~='icon']");
    if (!existingicon) {
      const link = document.createElement("link");
      link.rel = "icon";
      link.href = "/images/assets/baliwasan.png";

      link.type = "image/png";
      document.head.appendChild(link);
    }

// const inputs = document.querySelectorAll(".inpAuth input");

// inputs.forEach((input, idx) => {
//   // Block non-digits
//   input.addEventListener("keydown", (e) => {
//     if (
//       e.key === "e" ||
//       e.key === "E" ||
//       e.key === "+" ||
//       e.key === "-" ||
//       e.key === "."
//     ) {
//       e.preventDefault();
//     }
//     // On Backspace from empty, move back
//     if (e.key === "Backspace" && !input.value && idx > 0) {
//       inputs[idx - 1].focus();
//     }
//   });

//   input.addEventListener("input", () => {
//     // Keep only first digit
//     input.value = input.value.replace(/\D/g, "").slice(0, 1);
//     // Move to next
//     if (input.value && idx < inputs.length - 1) {
//       inputs[idx + 1].focus();
//     }
//   });

//   // Optional: handle paste of full OTP
//   input.addEventListener("paste", (e) => {
//     e.preventDefault();
//     const paste = (e.clipboardData || window.clipboardData)
//       .getData("text")
//       .replace(/\D/g, "");
//     paste.split("").forEach((ch, i) => {
//       if (idx + i < inputs.length) {
//         inputs[idx + i].value = ch;
//       }
//     });
//     const nextIdx = Math.min(idx + paste.length, inputs.length - 1);
//     inputs[nextIdx].focus();
//   });
// });

window.addEventListener("beforeunload", () => {
    document.body.classList.add("fade-out");
  });