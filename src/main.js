import { initAuth, handleLogout } from './auth.js';
import { initAdminDashboard } from './admin.js';
import { initLecturerDashboard } from './lecturer.js';
import { initStudentDashboard } from './student.js';

const app = document.getElementById('app');

function showPage(pageName) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById(`${pageName}-page`).classList.add('active');
}

function setupNavigation() {
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
      if (link.id === 'logout-btn') {
        e.preventDefault();
        handleLogout();
        return;
      }

      const section = link.dataset.section;
      if (section) {
        e.preventDefault();
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.getElementById(section).classList.add('active');
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
      }
    });
  });
}

async function init() {
  setupNavigation();
  await initAuth();

  document.addEventListener('userRoleChanged', (e) => {
    const role = e.detail.role;

    if (role === 'admin') {
      showPage('admin');
      initAdminDashboard();
    } else if (role === 'lecturer') {
      showPage('lecturer');
      initLecturerDashboard();
    } else if (role === 'student') {
      showPage('student');
      initStudentDashboard();
    }
  });
}

init();
