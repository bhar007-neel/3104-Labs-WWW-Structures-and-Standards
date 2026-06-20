document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('.registration-form');
  const fullNameInput = document.getElementById('full-name');
  const emailInput = document.getElementById('email');
  const phoneInput = document.getElementById('phone');
  const ageSelect = document.getElementById('age');
  const tierRadios = document.querySelectorAll('input[name="tier"]');
  const contactMethodSelect = document.getElementById('contact-method');
  const startDateInput = document.getElementById('start-date');
  const aboutTextarea = document.getElementById('about');

  function createErrorMessage(fieldName) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = fieldName;
    return errorDiv;
  }

  function clearErrors() {
    const existingErrors = document.querySelectorAll('.form-error');
    existingErrors.forEach(error => error.remove());
  }

  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function validateForm() {
    clearErrors();
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
    }

    // Validate age selection
    if (ageSelect.value === '') {
      const error = createErrorMessage('Please select an age range.');
      ageSelect.parentElement.appendChild(error);
      isValid = false;
    }

    // Validate membership tier selection
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

  form.addEventListener('submit', function(event) {
    if (!validateForm()) {
      event.preventDefault();
    }
  });
});
