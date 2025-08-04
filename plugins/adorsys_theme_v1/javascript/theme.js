// JS toggle for light/dark mode using Tailwind’s “dark” class
document.addEventListener('DOMContentLoaded', () => {
  // Create toggle button
  const toggle = document.createElement('button');
  toggle.id = 'theme-toggle';
  toggle.innerText = '🌓';
  toggle.className = 'fixed top-4 right-4 p-2 bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded shadow-lg z-50';
  document.body.appendChild(toggle);

  // Initialize theme from localStorage
  const stored = localStorage.getItem('theme');
  if (stored === 'dark') {
    document.documentElement.classList.add('dark');
  }

  // Toggle on click
  toggle.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
  });
});