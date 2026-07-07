document.addEventListener('DOMContentLoaded', initAccordion);

/**
 * Wires click listeners to every .accordion-btn on the page.
 * Safe to call when no accordion exists — does nothing if none found.
 */
function initAccordion() {
  const buttons = document.querySelectorAll('.accordion-btn');
  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      toggleAccordionItem(btn);
    });
  });
}

/**
 * Toggles one accordion item open or closed.
 * Reads the current aria-expanded state, flips it, and reflects the change
 * on both the button (aria-expanded) and its controlled panel (classList).
 */
function toggleAccordionItem(btn) {
  var expanded = btn.getAttribute('aria-expanded') === 'true';
  var panelId = btn.getAttribute('aria-controls');
  var panel = document.getElementById(panelId);

  btn.setAttribute('aria-expanded', String(!expanded));

  if (panel) {
    panel.classList.toggle('accordion-panel--open', !expanded);
  }
}
