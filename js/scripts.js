function validateField(field, regex, errorMessage, errorElement) {
  const isValid = regex.test(field.value);
  field.style.borderColor = isValid ? "green" : "red";
  errorElement.textContent = isValid ? "" : errorMessage;
  errorElement.style.color = "red";
  return isValid;
}

function calculatePasswordStrength(password) {
  let strength = 0;
  if (password.length >= 6) strength += 1;
  if (/[A-Z]/.test(password)) strength += 1;
  if (/[0-9]/.test(password)) strength += 1;
  if (/[^A-Za-z0-9]/.test(password)) strength += 1;
  return strength;
}

function updatePasswordStrength() {
  const password = document.getElementById("password").value;
  const strengthBar = document.getElementById("password-strength");
  const strength = calculatePasswordStrength(password);
  const strengthPercent = (strength / 4) * 100;

  strengthBar.style.width = strengthPercent + "%";
  if (strength === 0) {
    strengthBar.style.backgroundColor = "#e2e8f0";
    strengthBar.textContent = "";
  } else if (strength <= 2) {
    strengthBar.style.backgroundColor = "#f87171";
    strengthBar.textContent = "Weak";
  } else if (strength === 3) {
    strengthBar.style.backgroundColor = "#fbbf24";
    strengthBar.textContent = "Medium";
  } else {
    strengthBar.style.backgroundColor = "#34d399";
    strengthBar.textContent = "Strong";
  }
}

function validateRegisterForm() {
  const name = document.getElementById("name");
  const registrationType = document.getElementById("registration_type").value;
  const email = document.getElementById("email");
  const phone = document.getElementById("phone");
  const password = document.getElementById("password");
  const submitButton = document.querySelector('button[type="submit"]');

  const nameValid = validateField(
    name,
    /.+/,
    "Name is required.",
    document.getElementById("name-error")
  );
  const passwordValid = validateField(
    password,
    /.{6,}/,
    "Password must be at least 6 characters.",
    document.getElementById("password-error")
  );
  let contactValid = false;

  if (registrationType === "email") {
    contactValid = validateField(
      email,
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
      "Invalid email address.",
      document.getElementById("email-error")
    );
  } else {
    contactValid = validateField(
      phone,
      /^[1-9]\d{1,14}$/,
      "Invalid phone number (e.g., 1234567890, without +).",
      document.getElementById("phone-error")
    );
  }

  const isFormValid = nameValid && contactValid && passwordValid;
  submitButton.disabled = !isFormValid;
  return isFormValid;
}

function toggleRegistrationTypeTab(type) {
  const emailField = document.getElementById("email-field");
  const phoneField = document.getElementById("phone-field");
  const registrationTypeInput = document.getElementById("registration_type");
  const tabs = document.querySelectorAll(".tab");

  // Update active tab
  tabs.forEach((tab) => {
    tab.classList.remove("active");
    if (tab.getAttribute("data-type") === type) {
      tab.classList.add("active");
    }
  });

  // Update hidden input value
  registrationTypeInput.value = type;

  // Toggle field visibility
  if (type === "email") {
    emailField.style.display = "block";
    phoneField.style.display = "none";
    emailField.classList.add("field-visible");
    phoneField.classList.remove("field-visible");
    document.getElementById("email").setAttribute("required", "required");
    document.getElementById("phone").removeAttribute("required");
  } else {
    emailField.style.display = "none";
    phoneField.style.display = "block";
    emailField.classList.remove("field-visible");
    phoneField.classList.add("field-visible");
    document.getElementById("email").removeAttribute("required");
    document.getElementById("phone").setAttribute("required", "required");
  }

  // Re-validate form after toggling
  validateRegisterForm();
}

function validateLoginForm() {
  const loginType = document.getElementById("login_type").value;
  const email = document.getElementById("email");
  const phone = document.getElementById("phone");
  const password = document.getElementById("password");
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRegex = /^[1-9]\d{1,14}$/;

  const passwordValid = validateField(
    password,
    /.+/,
    "Password is required.",
    document.getElementById("password-error")
  );
  let contactValid = false;

  if (loginType === "email") {
    contactValid = validateField(
      email,
      emailRegex,
      "Invalid email address.",
      document.getElementById("email-error")
    );
  } else {
    contactValid = validateField(
      phone,
      phoneRegex,
      "Invalid phone number (e.g., 1234567890, without +).",
      document.getElementById("phone-error")
    );
  }

  return passwordValid && contactValid;
}

function toggleLoginTypeTab(type) {
  const emailField = document.getElementById("email-field");
  const phoneField = document.getElementById("phone-field");
  const loginTypeInput = document.getElementById("login_type");
  const tabs = document.querySelectorAll(".tab");

  // Update active tab
  tabs.forEach((tab) => {
    tab.classList.remove("active");
    if (tab.getAttribute("data-type") === type) {
      tab.classList.add("active");
    }
  });

  // Update hidden input value
  loginTypeInput.value = type;

  // Toggle field visibility
  if (type === "email") {
    emailField.style.display = "block";
    phoneField.style.display = "none";
    emailField.classList.add("field-visible");
    phoneField.classList.remove("field-visible");
    document.getElementById("email").setAttribute("required", "required");
    document.getElementById("phone").removeAttribute("required");
  } else {
    emailField.style.display = "none";
    phoneField.style.display = "block";
    emailField.classList.remove("field-visible");
    phoneField.classList.add("field-visible");
    document.getElementById("email").removeAttribute("required");
    document.getElementById("phone").setAttribute("required", "required");
  }
}

function togglePasswordVisibility() {
  const passwordField = document.getElementById("password");
  const toggleIcon = document.querySelector(".password-toggle");
  if (passwordField.type === "password") {
    passwordField.type = "text";
    toggleIcon.textContent = "👁️‍🗨️";
  } else {
    passwordField.type = "password";
    toggleIcon.textContent = "👁️";
  }
}

function filterShows() {
  const genre = document.getElementById("genre").value.toLowerCase();
  const cards = document.querySelectorAll(".card");

  cards.forEach((card) => {
    const cardGenre = card.getAttribute("data-genre").toLowerCase();
    if (genre === "" || cardGenre === genre) {
      card.style.display = "block";
    } else {
      card.style.display = "none";
    }
  });
}

// Initialize real-time validation and tab functionality on page load
document.addEventListener("DOMContentLoaded", () => {
  // Register page initialization
  const registerFields = ["name", "email", "phone", "password"];
  registerFields.forEach((fieldId) => {
    const field = document.getElementById(fieldId);
    if (field) {
      field.addEventListener("input", validateRegisterForm);
    }
  });

  const passwordField = document.getElementById("password");
  if (passwordField) {
    passwordField.addEventListener("input", updatePasswordStrength);
  }

  const registerTabs = document.querySelectorAll(".tab[data-type]");
  registerTabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const type = tab.getAttribute("data-type");
      if (document.querySelector("body.register-page")) {
        toggleRegistrationTypeTab(type);
      } else if (document.querySelector("body.login-page")) {
        toggleLoginTypeTab(type);
      }
    });
  });

  // Set default tab for register page
  if (document.querySelector("body.register-page")) {
    toggleRegistrationTypeTab("email");
    validateRegisterForm();
  }

  // Set default tab for login page
  if (document.querySelector("body.login-page")) {
    toggleLoginTypeTab("email");
  }
});
