function toggleDropdown() {
  const container = document.getElementById('accountTypeSelect');
  container.classList.toggle('open');
}

function selectRole(roleValue, displayText) {
  document.getElementById('txt_tipo_cuenta').value = roleValue;

  const selectedText = document.getElementById('selectedText');
  const triggerIcon = document.getElementById('triggerIcon');
  const trigger = document.getElementById('selectTrigger');
  const container = document.getElementById('accountTypeSelect');

  selectedText.innerText = displayText;
  selectedText.classList.add('selected');
  trigger.classList.add('active');
  triggerIcon.classList.add('active');

  if (roleValue === 'anfitrion') {
    triggerIcon.innerHTML = `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z"></path></svg>`;
  } else {
    triggerIcon.innerHTML = `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>`;
  }

  document.querySelectorAll('.dropdown-option').forEach(opt => opt.classList.remove('selected'));
  document.getElementById(`opt-${roleValue}`).classList.add('selected');

  const notice = document.getElementById('organizerNotice');
  const hostFields = document.getElementById('host-fields');
  const usernameInput = document.getElementById('txt_username');
  const nameLabel = document.getElementById('name-label');

  if (roleValue === 'anfitrion') {
    notice.style.display = 'block';
    hostFields.classList.remove('hidden');
    hostFields.style.display = 'block';
    usernameInput.setAttribute('required', 'required');
    nameLabel.textContent = 'Nombre público o de organización';
  } else {
    notice.style.display = 'none';
    hostFields.classList.add('hidden');
    hostFields.style.display = 'none';
    usernameInput.removeAttribute('required');
    usernameInput.value = '';
    nameLabel.textContent = 'Nombre completo';
  }

  // Cerrar el menú desplegable apenas se elige el rol
  container.classList.remove('open');
}

document.addEventListener('click', function(e) {
  const container = document.getElementById('accountTypeSelect');
  if (container && !container.contains(e.target)) {
    container.classList.remove('open');
  }
});

function togglePasswordVisibility() {
  const passwordInput = document.getElementById('txt_password');
  const eyeIcon = document.getElementById('eye-icon');

  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    eyeIcon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
  } else {
    passwordInput.type = 'password';
    eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
  }
}

function validateForm() {
  const roleValue = document.getElementById('txt_tipo_cuenta').value;
  if (!roleValue) {
    alert("Por favor elegí cómo vas a usar EventNow para continuar.");
    toggleDropdown();
    return false;
  }
  return true;
}