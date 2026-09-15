document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const input = toggle.parentElement.querySelector('input');

        if (!input) {
            return;
        }

        const setVisible = (visible) => {
            input.type = visible ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(visible));
            toggle.title = visible ? 'Hide password' : 'Show password';
        };

        // Keeps the cursor in the input when clicking, so Enter still submits the form.
        toggle.addEventListener('mousedown', (event) => event.preventDefault());

        toggle.addEventListener('click', () => {
            setVisible(input.type === 'password');
        });

        // The change-password dialog resets its form each time it opens; start hidden again.
        input.form?.addEventListener('reset', () => setVisible(false));
    });
});
