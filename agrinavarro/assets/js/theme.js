(function () {
  var themeKey = 'datacorepoint-theme';
  var toggleButtons = document.querySelectorAll('[data-theme-toggle]');

  function applyTheme(theme) {
    var isDark = theme === 'dark';
    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';

    toggleButtons.forEach(function (button) {
      var icon = button.querySelector('i');
      button.setAttribute('aria-pressed', String(isDark));
      button.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      button.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      if (icon) {
        icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
      }
    });
  }

  var savedTheme = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
  applyTheme(savedTheme);

  toggleButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      var nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      applyTheme(nextTheme);

      try {
        window.localStorage.setItem(themeKey, nextTheme);
      } catch (error) {
        console.warn('Theme preference could not be saved.', error);
      }
    });
  });
}());
