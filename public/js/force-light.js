// Отключаем темную тему принудительно
document.addEventListener('DOMContentLoaded', function() {
    // Удаляем класс dark с html
    document.documentElement.classList.remove('dark');

    // Отключаем Alpine store dark mode
    if (window.Alpine && window.Alpine.store && window.Alpine.store('darkMode')) {
        window.Alpine.store('darkMode').on = false;
    }

    // Удаляем слушатель системной темы
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener = function() {};

    // Сохраняем в localStorage
    localStorage.setItem('moonshine_dark_mode', '0');
    localStorage.setItem('dark_mode', '0');

    // Удаляем переключатель темы из DOM
    const themeSwitcher = document.querySelector('.theme-switcher');
    if (themeSwitcher) {
        themeSwitcher.remove();
    }
});

// Перехватываем событие toggle
document.addEventListener('darkMode:toggle', function(e) {
    e.preventDefault();
    e.stopPropagation();

    // Принудительно устанавливаем светлую тему
    document.documentElement.classList.remove('dark');
    if (window.Alpine && window.Alpine.store && window.Alpine.store('darkMode')) {
        window.Alpine.store('darkMode').on = false;
    }
});
