document.addEventListener('DOMContentLoaded', function() {
  // Cache all form inputs once so they don't need to be queried on every validation run.
  const form = document.querySelector('.registration-form');
  const formMessage = document.querySelector('.form-message');
  const fullNameInput = document.getElementById('full-name');
  const emailInput = document.getElementById('email');
  const phoneInput = document.getElementById('phone');
  const ageSelect = document.getElementById('age');
  const tierRadios = document.querySelectorAll('input[name="tier"]');
  const contactMethodSelect = document.getElementById('contact-method');
  const startDateInput = document.getElementById('start-date');
  const aboutTextarea = document.getElementById('about');

  // Creates a styled error div with the given message text.
  function createErrorMessage(fieldName) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = fieldName;
    return errorDiv;
  }

  // Removes all existing error messages before each validation run.
  function clearErrors() {
    const existingErrors = document.querySelectorAll('.form-error');
    existingErrors.forEach(error => error.remove());
  }

  // Clears the success message so it doesn't persist across validation runs.
  function clearFormMessage() {
    if (formMessage) {
      formMessage.textContent = '';
      formMessage.classList.remove('form-message--success');
    }
  }

  // Reveals the success confirmation in the .form-message element.
  function showSuccessMessage() {
    if (!formMessage) {
      return;
    }
    formMessage.textContent = 'Registration submitted successfully. We will contact you soon with the next steps.';
    formMessage.classList.add('form-message--success');
  }

  // Returns true if the email matches a basic format: characters@domain.tld
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Phone must contain at least 10 characters made up of digits, spaces, dashes,
  // parentheses, or a leading +. Accepts formats like 613-123-4567 and +1 613 123 4567.
  function validatePhone(phone) {
    const phoneRegex = /^\+?[\d\s\-().]{10,}$/;
    return phoneRegex.test(phone);
  }

  // Runs all field checks, appends errors next to failing fields, and returns
  // true only when every field passes.
  function validateForm() {
    clearErrors();
    clearFormMessage();
    let isValid = true;

    // Validate full name
    if (fullNameInput.value.trim() === '') {
      const error = createErrorMessage('Please enter your full name.');
      fullNameInput.parentElement.appendChild(error);
      isValid = false;
    }

    // Validate email
    if (emailInput.value.trim() === '') {
      const error = createErrorMessage('Please enter your email address.');
      emailInput.parentElement.appendChild(error);
      isValid = false;
    } else if (!validateEmail(emailInput.value)) {
      const error = createErrorMessage('Please enter a valid email address.');
      emailInput.parentElement.appendChild(error);
      isValid = false;
    }

    // Validate phone number
    if (phoneInput.value.trim() === '') {
      const error = createErrorMessage('Please enter your phone number.');
      phoneInput.parentElement.appendChild(error);
      isValid = false;
    } else if (!validatePhone(phoneInput.value.trim())) {
      const error = createErrorMessage('Please enter a valid phone number (at least 10 digits).');
      phoneInput.parentElement.appendChild(error);
      isValid = false;
    }

    // Validate age selection
    if (ageSelect.value === '') {
      const error = createErrorMessage('Please select an age range.');
      ageSelect.parentElement.appendChild(error);
      isValid = false;
    }

    // Validate membership tier — checks that at least one radio button is selected.
    const tierSelected = Array.from(tierRadios).some(radio => radio.checked);
    if (!tierSelected) {
      const tierContainer = document.querySelector('.radio-group');
      const error = createErrorMessage('Please select a membership plan.');
      tierContainer.parentElement.appendChild(error);
      isValid = false;
    }

    // Validate contact method
    if (contactMethodSelect.value === '') {
      const error = createErrorMessage('Please select a preferred contact method.');
      contactMethodSelect.parentElement.appendChild(error);
      isValid = false;
    }

    // Validate start date
    if (startDateInput.value.trim() === '') {
      const error = createErrorMessage('Please select a preferred start date.');
      startDateInput.parentElement.appendChild(error);
      isValid = false;
    }

    // Validate about/goals textarea
    if (aboutTextarea.value.trim() === '') {
      const error = createErrorMessage('Please tell us about your goals.');
      aboutTextarea.parentElement.appendChild(error);
      isValid = false;
    }

    return isValid;
  }

  // Always prevent default so the page never navigates away.
  // Invalid path shows errors; valid path resets the form and shows the confirmation.
  form.addEventListener('submit', function(event) {
    if (!validateForm()) {
      event.preventDefault();
      return;
    }

    event.preventDefault();
    form.reset();
    showSuccessMessage();
  });
});
