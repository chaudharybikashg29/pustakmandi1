// =======================================
// PUSTAKMANDI LOGIN PAGE
// login.js
// =======================================

// =======================================
// SELECT ELEMENTS
// =======================================

const passwordInput = document.getElementById("password");

const togglePassword = document.getElementById("togglePassword");

const loginForm = document.querySelector(".login-form");

const loginInput = document.getElementById("login");

// =======================================
// SHOW / HIDE PASSWORD
// =======================================

togglePassword.addEventListener("click", () => {
  const icon = togglePassword.querySelector("i");

  if (passwordInput.type === "password") {
    passwordInput.type = "text";

    icon.classList.remove("fa-eye");

    icon.classList.add("fa-eye-slash");
  } else {
    passwordInput.type = "password";

    icon.classList.remove("fa-eye-slash");

    icon.classList.add("fa-eye");
  }
});

// =======================================
// FORM VALIDATION
// =======================================

loginForm.addEventListener("submit", function (e) {
  const login = loginInput.value.trim();

  const password = passwordInput.value;

  // ===================================
  // EMPTY FIELD VALIDATION
  // ===================================

  if (login === "" || password === "") {
    e.preventDefault();

    showToast("Please fill all fields", "#dc2626");

    return;
  }

  // ===================================
  // PASSWORD LENGTH
  // ===================================

  if (password.length < 8) {
    e.preventDefault();

    showToast("Password must be at least 8 characters", "#dc2626");

    passwordInput.focus();

    return;
  }

  // ===================================
  // ALLOW FORM TO SUBMIT
  // ===================================

  // No e.preventDefault() here.
  // The form will go to login.php.
});

// =======================================
// PRESS ENTER TO LOGIN
// =======================================

document.addEventListener("keydown", function (e) {
  if (e.key === "Enter") {
    loginForm.requestSubmit();
  }
});

// =======================================
// TOAST NOTIFICATION
// =======================================

function showToast(message, color) {
  const toast = document.createElement("div");

  toast.innerText = message;

  toast.style.position = "fixed";

  toast.style.top = "20px";

  toast.style.right = "20px";

  toast.style.padding = "15px 25px";

  toast.style.background = color;

  toast.style.color = "#fff";

  toast.style.borderRadius = "8px";

  toast.style.boxShadow = "0 10px 20px rgba(0,0,0,.25)";

  toast.style.fontWeight = "600";

  toast.style.zIndex = "9999";

  toast.style.opacity = "0";

  toast.style.transition = ".3s";

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = "1";
  }, 100);

  setTimeout(() => {
    toast.style.opacity = "0";

    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 2500);
}

// =======================================
// INPUT ANIMATION
// =======================================

const inputs = document.querySelectorAll("input");

inputs.forEach((input) => {
  input.addEventListener("focus", () => {
    if (input.parentElement.classList.contains("input-box")) {
      input.parentElement.style.transform = "scale(1.02)";
    }
  });

  input.addEventListener("blur", () => {
    if (input.parentElement.classList.contains("input-box")) {
      input.parentElement.style.transform = "scale(1)";
    }
  });
});
