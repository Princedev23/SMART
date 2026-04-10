import { login, logout, checkAuth } from './api-client.js';

let currentUser = null;
let currentRole = null;

export async function initAuth() {
  const loginForm = document.getElementById('login-form');
  const roleButtons = document.querySelectorAll('.role-btn');

  let selectedRole = 'student';

  roleButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      roleButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selectedRole = btn.dataset.role;
    });
  });

  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorEl = document.getElementById('login-error');

    errorEl.textContent = '';

    try {
      const response = await login(email, password, selectedRole);

      if (!response.success) {
        errorEl.textContent = response.error || 'Invalid credentials';
        return;
      }

      currentUser = response.user;
      currentRole = response.user.role;

      loginForm.reset();
      saveSession();
      document.dispatchEvent(new CustomEvent('userRoleChanged', {
        detail: { role: currentRole }
      }));
    } catch (error) {
      errorEl.textContent = 'An error occurred';
      console.error(error);
    }
  });

  checkExistingSession();
}

export function getCurrentUser() {
  return currentUser;
}

export function getCurrentRole() {
  return currentRole;
}

export async function handleLogout() {
  try {
    await logout();
  } catch (error) {
    console.error(error);
  }

  currentUser = null;
  currentRole = null;
  sessionStorage.removeItem('userSession');
  document.getElementById('login-form').reset();
  document.getElementById('email').value = '';
  document.getElementById('password').value = '';
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('login-page').classList.add('active');
}

export function saveSession() {
  if (currentUser) {
    sessionStorage.setItem('userSession', JSON.stringify({
      user: currentUser,
      role: currentRole
    }));
  }
}

function checkExistingSession() {
  const savedSession = sessionStorage.getItem('userSession');
  if (savedSession) {
    const session = JSON.parse(savedSession);
    currentUser = session.user;
    currentRole = session.role;
    document.dispatchEvent(new CustomEvent('userRoleChanged', {
      detail: { role: currentRole }
    }));
  }
}
