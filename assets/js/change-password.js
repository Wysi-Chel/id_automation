document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('change-password-dialog');
    const openButton = document.querySelector('[data-change-password-open]');

    if (!dialog || !openButton) {
        return;
    }

    const form = dialog.querySelector('form');
    const newPassword = form.elements.new_password;
    const confirmPassword = form.elements.confirm_password;

    const checkPasswordsMatch = () => {
        confirmPassword.setCustomValidity(
            confirmPassword.value !== '' && confirmPassword.value !== newPassword.value
                ? 'The new passwords do not match.'
                : ''
        );
    };

    openButton.addEventListener('click', () => {
        form.reset();
        checkPasswordsMatch();
        dialog.showModal();
    });

    dialog.querySelector('[data-change-password-close]').addEventListener('click', () => {
        dialog.close();
    });

    newPassword.addEventListener('input', checkPasswordsMatch);
    confirmPassword.addEventListener('input', checkPasswordsMatch);
});
