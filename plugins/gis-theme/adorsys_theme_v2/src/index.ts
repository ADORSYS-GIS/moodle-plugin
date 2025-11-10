// Import Tailwind CSS (v4) - processed by PostCSS
import "./styles/tailwind.css";

// Import Moodle SCSS styles - processed by pure Sass (no PostCSS)
import "../scss/moodle.scss";

console.log("Adorsys Moodle theme initialized.");

// Preloader Script - Shows preloader only when page content is not ready
// If page content is available, no preloader is needed
// If page content is not yet available, preloader displays until content is ready

function hidePreloader() {
    const preloader = document.querySelector('.preloader-wrapper') as HTMLElement;
    
    if (!preloader) return; // Exit if preloader doesn't exist
    
    // Page content is ready, hide preloader with fade-out effect
    preloader.style.opacity = '0';
    preloader.style.transition = 'opacity 0.5s ease';
    
    // Remove preloader from DOM after fade animation completes
    setTimeout(function() {
        preloader.style.display = 'none';
    }, 500);
}

// Check if page content is already available
if (document.readyState === 'complete') {
    // Page content is already available, no preloader needed
    hidePreloader();
} else {
    // Page content is not yet available, keep preloader visible
    // Wait until all page content (images, stylesheets, scripts, fonts, etc.) is ready
    window.addEventListener('load', function() {
        // Page content is now ready, hide the preloader
        hidePreloader();
    });
}
