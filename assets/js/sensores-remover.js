const removalForm = document.querySelector('[data-removal-form]');

if (removalForm) {
  const confirmationInput = removalForm.querySelector('[name="confirmacao"]');
  const removeButton = removalForm.querySelector('[data-remove-button]');
  const expectedCode = removalForm.dataset.sensorCode;

  if (confirmationInput && removeButton) {
    const toggleButton = () => {
      removeButton.disabled = confirmationInput.value.trim() !== expectedCode;
    };

    confirmationInput.addEventListener('input', toggleButton);
    toggleButton();

    removalForm.addEventListener('submit', () => {
      removeButton.disabled = true;
    });
  }
}
