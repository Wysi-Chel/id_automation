document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-public-file]').forEach(function (input) {
        input.addEventListener('change', function () {
            var label = input.closest('.public-file-drop')?.querySelector('[data-file-label]');
            if (!label) return;
            var file = input.files?.[0];
            if (!file) {
                label.textContent = 'No file selected';
                return;
            }
            var size = file.size < 1024 * 1024
                ? Math.ceil(file.size / 1024) + ' KB'
                : (file.size / 1024 / 1024).toFixed(1) + ' MB';
            label.textContent = file.name + ' · ' + size;
        });
    });
});
