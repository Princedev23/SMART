import { login } from './api-client.js';

let currentUser = null;
let currentRole = null;

export async function initAuth() {
  console.log('Initializing auth...');
  
  const loginForm = document.getElementById('login-form');
  const roleButtons = document.querySelectorAll('.role-btn');
  
  let selectedRole = 'student';
  
  // Set default role
  if (roleButtons.length > 0) {
    roleButtons.forEach(btn => {
      if (btn.dataset.role === 'student') {
        btn.classList.add('active');
      }
    });
  }
  
  // Role selection
  roleButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      roleButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selectedRole = btn.dataset.role;
      console.log('Selected role:', selectedRole);
    });
  });
  
  
  
  // Check for existing session
  checkExistingSession();
  
  console.log('Auth initialization complete');
}

export function getCurrentUser() {
  return currentUser;
}

export function getCurrentRole() {
  return currentRole;
}

export function handleLogout() {
  console.log('Logout initiated');
  
  // Clear user data
  currentUser = null;
  currentRole = null;
  
  // Clear session storage
  sessionStorage.removeItem('userSession');
  localStorage.removeItem('userSession');
  
  // Reset form
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.reset();
  }
  
  // Clear input fields
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  if (emailInput) emailInput.value = '';
  if (passwordInput) passwordInput.value = '';
  
  // Reset role buttons
  const roleButtons = document.querySelectorAll('.role-btn');
  roleButtons.forEach(btn => btn.classList.remove('active'));
  if (roleButtons.length > 0) {
    roleButtons[0].classList.add('active');
  }
  
  // Show welcome page instead of login after logout
document.querySelectorAll('.page').forEach(p => {
    p.classList.remove('active');
    p.style.display = '';
  });
  const welcomePage = document.getElementById('welcome-page');
  if (welcomePage) {
    welcomePage.classList.add('active');
    welcomePage.style.display = '';
  }
  
  console.log('Logout complete - showing welcome page');
}

export function saveSession() {
  if (currentUser) {
    const sessionData = {
      user: currentUser,
      role: currentRole,
      timestamp: new Date().getTime()
    };
    sessionStorage.setItem('userSession', JSON.stringify(sessionData));
    localStorage.setItem('userSession', JSON.stringify(sessionData));
    console.log('Session saved');
  }
}

function checkExistingSession() {
  console.log('Checking for existing session...');
  
  const savedSession = sessionStorage.getItem('userSession') || localStorage.getItem('userSession');
  
  if (savedSession) {
    try {
      const session = JSON.parse(savedSession);
      console.log('Existing session found:', session);
      // Keep user on welcome page, they need to click Get Started
    } catch (error) {
      console.error('Error parsing session:', error);
      sessionStorage.removeItem('userSession');
      localStorage.removeItem('userSession');
    }
  } else {
    console.log('No existing session found');
  }
}

