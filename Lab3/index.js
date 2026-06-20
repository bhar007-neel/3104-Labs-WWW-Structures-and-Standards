document.addEventListener('DOMContentLoaded', function() {
  const modalShown = localStorage.getItem('welcomeModalShown');

  if (!modalShown) {
    const modal = document.createElement('div');
    modal.className = 'welcome-modal';

    const content = document.createElement('div');
    content.className = 'welcome-modal-content';

    const heading = document.createElement('h2');
    heading.textContent = '👋 Welcome to Uranus Fitness!';

    const paragraph = document.createElement('p');
    paragraph.textContent = 'Start your fitness journey today and become part of our community.';

    const button = document.createElement('button');
    button.className = 'welcome-modal-button';
    button.textContent = 'Get Started';
    button.addEventListener('click', function() {
      modal.classList.add('hidden');
      localStorage.setItem('welcomeModalShown', 'true');
    });

    content.appendChild(heading);
    content.appendChild(paragraph);
    content.appendChild(button);
    modal.appendChild(content);
    document.body.appendChild(modal);
  }
});
