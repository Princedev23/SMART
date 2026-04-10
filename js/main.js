import { login } from './api-client.js';
import { handleLogout } from './auth.js';
import { initAdminDashboard } from './admin.js';
import { initLecturerDashboard } from './lecturer.js';
import { initStudentDashboard } from './student.js';

//sidebar toggle
window.toggleSidebar = function(btn) {
  const page = btn.closest('.page');
  const sidebar = page.querySelector('.sidebar');
  const overlay = page.querySelector('.sidebar-overlay');
  sidebar.classList.toggle('open');
  overlay.classList.toggle('open');
};

window.closeSidebar = function(overlay) {
  const page = overlay.closest('.page');
  const sidebar = page.querySelector('.sidebar');
  sidebar.classList.remove('open');
  overlay.classList.remove('open');
};

// mobile
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('nav-link')) {
    const page = e.target.closest('.page');
    if (page) {
      const sidebar = page.querySelector('.sidebar');
      const overlay = page.querySelector('.sidebar-overlay');
      if (sidebar) sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('open');
    }
  }
});

function showPage(pageName) {
  console.log('showPage called with:', pageName);
  
  // Get all pages
  const pages = document.querySelectorAll('.page');
  
  // Remove active from all pages and hide them
pages.forEach(p => {
    p.classList.remove('active');
    p.style.display = '';
  });
  
  // Show target page
  const targetPage = document.getElementById(pageName + '-page');
  if (targetPage) {
    targetPage.classList.add('active');
    targetPage.style.display = '';
    console.log('Showing:', pageName + '-page');
    
    // Initialize the appropriate dashboard
    if (pageName === 'admin') {
      console.log('Initializing admin dashboard...');
      initAdminDashboard();
    } else if (pageName === 'lecturer') {
      console.log('Initializing lecturer dashboard...');
      initLecturerDashboard();
    } else if (pageName === 'student') {
      console.log('Initializing student dashboard...');
      initStudentDashboard();
    }
  } else {
    console.error('Page not found:', pageName + '-page');
  }
}

function setupNavigation() {
  console.log('Setting up navigation...');
  
  // Get Started button
  const getStartedBtn = document.getElementById('get-started-btn');
  if (getStartedBtn) {
    getStartedBtn.onclick = function(e) {
      if (e) e.preventDefault();
      console.log('Get Started clicked');
      showPage('login');
      return false;
    };
  }
  
  // Role buttons
  const roleButtons = document.querySelectorAll('.role-btn');
  
  roleButtons.forEach(btn => {
    btn.onclick = function(e) {
      if (e) e.preventDefault();
      roleButtons.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      console.log('Role selected:', this.dataset.role);
      return false;
    };
  });
  
  // Login form
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.onsubmit = function(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      
      console.log('=== LOGIN FORM SUBMITTED ===');
      
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const errorEl = document.getElementById('login-error');
      
      if (errorEl) errorEl.textContent = '';
      
      if (!email || !password) {
        if (errorEl) errorEl.textContent = 'Please enter email and password';
        return false;
      }
      
      // Get current role
      const activeBtn = document.querySelector('.role-btn.active');
      const currentRole = activeBtn ? activeBtn.dataset.role : 'student';
      console.log('Using role:', currentRole);
      
      // Call login API
      login(email, password, currentRole).then(response => {
        console.log('API Response:', response);
        
        if (!response.success) {
          if (errorEl) errorEl.textContent = response.error || 'Invalid credentials';
          return false;
        }
        
        console.log('Login SUCCESS! User:', response.user);
        
        // Reset form
        loginForm.reset();
        
        const role = response.user.role;
        console.log('Showing dashboard for:', role);
        
        // Show the page
        showPage(role);
        
        return false;
      }).catch(error => {
        console.error('Login error:', error);
        if (errorEl) errorEl.textContent = 'Could not reach server. Check your connection.';
        return false;
      });
      
      return false;
    };
  }
  
  // Logout buttons
  document.querySelectorAll('#logout-btn').forEach(btn => {
    btn.onclick = function(e) {
      if (e) e.preventDefault();
      console.log('Logout clicked');
      handleLogout();
      showPage('welcome');
      return false;
    };
  });
  
  console.log('Setup complete');
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    setupNavigation();
  });
} else {
  setupNavigation();
}
