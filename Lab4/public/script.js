document.addEventListener('DOMContentLoaded', highlightActiveNavLink);

/**
 * Reads the current page filename from the URL and sets aria-current="page"
 * on the matching nav link. Runs on every page so the active state is never
 * hardcoded in HTML. Falls back to index.html when the URL ends with a slash
 * (directory root served by a local server).
 */
function highlightActiveNavLink() {
  var currentFile = window.location.pathname.split('/').pop() || 'index.html';
  var navLinks = document.querySelectorAll('.nav-links a');

  navLinks.forEach(function (link) {
    if (link.getAttribute('href') === currentFile) {
      link.setAttribute('aria-current', 'page');
    }
  });
}
