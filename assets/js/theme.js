(function () {
    var root = document.documentElement;
    var storageKey = 'micei-theme';

    var currentTheme = function () {
        return root.dataset.theme === 'dark' ? 'dark' : 'light';
    };

    var updateButtons = function () {
        var isDark = currentTheme() === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            button.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            button.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            var label = button.querySelector('[data-theme-label]');
            if (label) label.textContent = isDark ? 'Light mode' : 'Dark mode';
        });
    };

    var applyTheme = function (theme, persist) {
        var normalized = theme === 'dark' ? 'dark' : 'light';
        root.dataset.theme = normalized;
        root.classList.toggle('dark-theme', normalized === 'dark');
        root.style.colorScheme = normalized;

        if (persist) {
            try {
                window.localStorage.setItem(storageKey, normalized);
                window.localStorage.setItem('systemMonitoringTheme', normalized);
            } catch (error) {
            }
        }
        updateButtons();
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                applyTheme(currentTheme() === 'dark' ? 'light' : 'dark', true);
            });
        });
        updateButtons();
    });

    window.addEventListener('storage', function (event) {
        if (event.key === storageKey && (event.newValue === 'dark' || event.newValue === 'light')) {
            applyTheme(event.newValue, false);
        }
    });
}());
