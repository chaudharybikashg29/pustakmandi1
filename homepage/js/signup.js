// ==========================================
// PUSTAKMANDI SIGNUP VALIDATION
// ==========================================

const form = document.querySelector(".signup-form");

const fullName = document.getElementById("full_name");
const email = document.getElementById("email");
const username = document.getElementById("username");
const phone = document.getElementById("phone");
const collegeName = document.getElementById("college_name");
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirmPassword");
const togglePassword = document.getElementById("togglePassword");
const role = document.getElementById("role");
const strengthBar = document.getElementById("strengthBar");
const error = document.getElementById("error");

// ==========================================
// SHOW / HIDE PASSWORD
// ==========================================

togglePassword.addEventListener("click", () => {
  const icon = togglePassword.querySelector("i");

  if (password.type === "password") {
    password.type = "text";

    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    password.type = "password";

    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
});

// ==========================================
// PASSWORD STRENGTH
// ==========================================

password.addEventListener("input", () => {
  const value = password.value;

  let strength = 0;

  // At least 8 characters
  if (value.length >= 8) {
    strength++;
  }

  // At least one uppercase
  if (/[A-Z]/.test(value)) {
    strength++;
  }

  // At least one lowercase
  if (/[a-z]/.test(value)) {
    strength++;
  }

  // At least one number
  if (/[0-9]/.test(value)) {
    strength++;
  }

  // At least one special character
  if (/[!@#$%^&*(),.?":{}|<>_\-+=/\\[\];']/.test(value)) {
    strength++;
  }

  // Reset
  strengthBar.style.width = "0%";
  strengthBar.style.background = "";

  if (strength === 1) {
    strengthBar.style.width = "20%";
    strengthBar.style.background = "red";
  } else if (strength === 2) {
    strengthBar.style.width = "40%";
    strengthBar.style.background = "orange";
  } else if (strength === 3) {
    strengthBar.style.width = "60%";
    strengthBar.style.background = "gold";
  } else if (strength === 4) {
    strengthBar.style.width = "80%";
    strengthBar.style.background = "lightgreen";
  } else if (strength === 5) {
    strengthBar.style.width = "100%";
    strengthBar.style.background = "green";
  }
});

// ==========================================
// FORM VALIDATION
// ==========================================

function validateForm() {

    // Clear previous error
    error.innerHTML = "";

    error.style.display = "none";


    // ==========================================
    // FULL NAME
    // ==========================================

    const fullNameValue = fullName.value.trim();

    if (fullNameValue === "") {

        showError("Full Name is required.");

        fullName.focus();

        return false;
    }


    const namePattern = /^[A-Za-z ]{3,100}$/;

    if (!namePattern.test(fullNameValue)) {

        showError(
            "Full Name must contain only letters and spaces."
        );

        fullName.focus();

        return false;
    }


    // ==========================================
    // EMAIL
    // ==========================================

    const emailValue = email.value.trim();

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    if (!emailPattern.test(emailValue)) {

        showError("Enter a valid email address.");

        email.focus();

        return false;
    }


    // ==========================================
    // USERNAME
    // ==========================================

    const usernameValue = username.value.trim();

    const usernamePattern =
        /^[A-Za-z0-9_]{4,50}$/;

    if (!usernamePattern.test(usernameValue)) {

        showError(
            "Username must be 4-50 characters and contain only letters, numbers and underscore."
        );

        username.focus();

        return false;
    }


    // ==========================================
    // PHONE
    // ==========================================

    const phoneValue = phone.value.trim();

    const phonePattern =
        /^(97|98)[0-9]{8}$/;

    if (!phonePattern.test(phoneValue)) {

        showError(
            "Enter a valid 10-digit Nepal phone number."
        );

        phone.focus();

        return false;
    }


    // ==========================================
    // COLLEGE
    // ==========================================

    if (collegeName.value === "") {

        showError("Please select your college.");

        collegeName.focus();

        return false;
    }


    // ==========================================
    // PASSWORD
    // ==========================================

    const passwordPattern =
        /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s]).{8,}$/;

    if (!passwordPattern.test(password.value)) {

        showError(
            "Password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character."
        );

        password.focus();

        return false;
    }


    // ==========================================
    // CONFIRM PASSWORD
    // ==========================================

    if (password.value !== confirmPassword.value) {

        showError("Passwords do not match.");

        confirmPassword.focus();

        return false;
    }


    // ==========================================
    // ROLE
    // ==========================================

    if (role.value !== "customer" && role.value !== "seller") {

        showError("Please select a valid role.");

        role.focus();

        return false;
    }


    // ==========================================
    // VALID
    // ==========================================

    return true;
}


// ==========================================
// SHOW ERROR
// ==========================================

function showError(message) {

    error.innerHTML = message;

    error.style.display = "block";

}


// ==========================================
// INPUT ANIMATION
// ==========================================

const inputs =
    document.querySelectorAll("input, select");

inputs.forEach((input) => {

    input.addEventListener("focus", () => {

        if (input.parentElement.classList.contains("input-box")) {

            input.parentElement.style.transform =
                "scale(1.02)";
        }

    });


    input.addEventListener("blur", () => {

        if (input.parentElement.classList.contains("input-box")) {

            input.parentElement.style.transform =
                "scale(1)";
        }

    });

});