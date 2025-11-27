// Language Management System
let currentLanguage = localStorage.getItem('language') || 'en';

function toggleLanguage() {
  currentLanguage = currentLanguage === 'en' ? 'fr' : 'en';
  localStorage.setItem('language', currentLanguage);
  updateLanguage();
}

function updateLanguage() {
  const langButton = document.getElementById('currentLang');
  if (langButton) {
    langButton.textContent = currentLanguage.toUpperCase();
  }

  // Update all elements with language attributes
  document.querySelectorAll('[data-lang-en]').forEach(element => {
    const text = currentLanguage === 'en' 
      ? element.getAttribute('data-lang-en')
      : element.getAttribute('data-lang-fr');
    
    // For elements with icon + text structure
    if (element.querySelector('i') && element.querySelector('span')) {
      const span = element.querySelector('span');
      span.textContent = text;
    } else if (element.querySelector('i')) {
      const icon = element.querySelector('i');
      const iconHTML = icon.outerHTML;
      element.innerHTML = iconHTML + ' ' + text;
    } else {
      element.textContent = text;
    }
  });

  // Update status badges if they exist
  document.querySelectorAll('.status-badge').forEach(badge => {
    if (badge.classList.contains('status-available')) {
      badge.textContent = currentLanguage === 'en' ? 'Available' : 'Disponible';
    } else if (badge.classList.contains('status-expired')) {
      badge.textContent = currentLanguage === 'en' ? 'Expired' : 'Expiré';
    } else if (badge.classList.contains('status-upcoming')) {
      badge.textContent = currentLanguage === 'en' ? 'Upcoming' : 'À Venir';
    } else if (badge.classList.contains('status-ongoing')) {
      badge.textContent = currentLanguage === 'en' ? 'Ongoing' : 'En Cours';
    } else if (badge.classList.contains('status-completed')) {
      badge.textContent = currentLanguage === 'en' ? 'Completed' : 'Terminé';
    } else if (badge.classList.contains('status-cancelled')) {
      badge.textContent = currentLanguage === 'en' ? 'Cancelled' : 'Annulé';
    }
  });

  // Update badges
  document.querySelectorAll('.badge').forEach(badge => {
    if (badge.classList.contains('positive')) {
      const icon = badge.querySelector('i');
      const text = currentLanguage === 'en' ? 'Popular' : 'Populaire';
      if (icon) {
        badge.innerHTML = icon.outerHTML + ' ' + text;
      }
    } else if (badge.classList.contains('negative')) {
      const icon = badge.querySelector('i');
      const text = currentLanguage === 'en' ? 'Negative' : 'Négatif';
      if (icon) {
        badge.innerHTML = icon.outerHTML + ' ' + text;
      }
    }
  });
}

function confirmDelete() {
  const message = currentLanguage === 'en' 
    ? 'Are you sure you want to delete this?'
    : 'Êtes-vous sûr de vouloir supprimer ceci ?';
  return confirm(message);
}

// Initialize language on page load
document.addEventListener('DOMContentLoaded', () => {
  updateLanguage();
});
