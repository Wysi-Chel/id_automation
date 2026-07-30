(function () {
    try {
        var savedTheme = window.localStorage
            ? window.localStorage.getItem('micei-theme') || window.localStorage.getItem('systemMonitoringTheme')
            : null;
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var theme = savedTheme === 'dark' || savedTheme === 'light'
            ? savedTheme
            : (prefersDark ? 'dark' : 'light');

        document.documentElement.dataset.theme = theme;
        document.documentElement.classList.toggle('dark-theme', theme === 'dark');
        document.documentElement.style.colorScheme = theme;
    } catch (error) {
    }
}());
