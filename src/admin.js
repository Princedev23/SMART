import { getCurrentUser, saveSession, handleLogout } from './auth.js';
import {
  getStudents,
  addStudent,
  deleteStudent,
  getLecturers,
  addLecturer,
  deleteLecturer,
  getMonthlyStats,
  getAdminStats,
  getMonthlyAttendanceRate,
  getAnnualAttendanceRate,
  sendNotifications
} from './api-client.js';

export async function initAdminDashboard() {
  saveSession();
  setupLogout();
  loadDashboardStats();
  setupStudentManagement();
  setupLecturerManagement();
  setupAttendanceReports();
  setupNotifications();
}

function setupLogout() {
  document.querySelectorAll('#logout-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      handleLogout();
    });
  });
}

async function loadDashboardStats() {
  try {
    const stats = await getAdminStats();
    if (stats.success) {
      document.getElementById('total-students').textContent = stats.data.total_students;
      document.getElementById('total-lecturers').textContent = stats.data.total_lecturers;
    }

    const now = new Date();
    const monthlyStats = await getMonthlyAttendanceRate(now.getMonth() + 1, now.getFullYear());
    if (monthlyStats.success) {
      document.getElementById('monthly-attendance').textContent = monthlyStats.data.rate + '%';
    }

    const annualStats = await getAnnualAttendanceRate(now.getFullYear());
    if (annualStats.success) {
      document.getElementById('annual-attendance').textContent = annualStats.data.rate + '%';
    }
  } catch (error) {
    console.error('Error loading dashboard stats:', error);
  }
}

function setupStudentManagement() {
  const addBtn = document.getElementById('add-student-btn');
  const cancelBtn = document.getElementById('cancel-student-btn');
  const form = document.getElementById('student-form');
  const formContainer = document.getElementById('student-form-container');
  const faceInput = document.getElementById('student-face');

  addBtn.addEventListener('click', () => {
    formContainer.classList.remove('hidden');
  });

  cancelBtn.addEventListener('click', () => {
    formContainer.classList.add('hidden');
    form.reset();
  });

  faceInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (event) => {
        const preview = document.getElementById('face-preview');
        preview.src = event.target.result;
        preview.classList.remove('hidden');
      };
      reader.readAsDataURL(file);
    }
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('student-form-error');
    errorEl.textContent = '';

    try {
      const formData = new FormData(form);
      const response = await addStudent(formData);

      if (!response.success) {
        errorEl.textContent = response.error || 'Error adding student';
        return;
      }

      formContainer.classList.add('hidden');
      form.reset();
      document.getElementById('face-preview').classList.add('hidden');

      alert(`Student added successfully!\nEmail: ${response.credentials.email}\nPassword: ${response.credentials.password}`);

      loadStudentsList();
      loadDashboardStats();
    } catch (error) {
      errorEl.textContent = 'Error adding student';
      console.error(error);
    }
  });

  loadStudentsList();
}

async function loadStudentsList() {
  try {
    const response = await getStudents();
    if (!response.success) {
      return;
    }

    const tbody = document.getElementById('students-tbody');
    tbody.innerHTML = '';

    for (const student of response.data) {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${student.name}</td>
        <td>${student.email}</td>
        <td>${student.department}</td>
        <td>${student.guardian_email}</td>
        <td>
          <button class="action-btn delete" data-id="${student.id}">Delete</button>
        </td>
      `;

      const deleteBtn = row.querySelector('.delete');
      deleteBtn.addEventListener('click', async () => {
        if (confirm('Are you sure you want to delete this student?')) {
          try {
            await deleteStudent(student.id);
            loadStudentsList();
            loadDashboardStats();
          } catch (error) {
            console.error(error);
          }
        }
      });

      tbody.appendChild(row);
    }
  } catch (error) {
    console.error('Error loading students:', error);
  }
}

function setupLecturerManagement() {
  const addBtn = document.getElementById('add-lecturer-btn');
  const cancelBtn = document.getElementById('cancel-lecturer-btn');
  const form = document.getElementById('lecturer-form');
  const formContainer = document.getElementById('lecturer-form-container');

  addBtn.addEventListener('click', () => {
    formContainer.classList.remove('hidden');
  });

  cancelBtn.addEventListener('click', () => {
    formContainer.classList.add('hidden');
    form.reset();
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('lecturer-form-error');
    errorEl.textContent = '';

    try {
      const formData = new FormData(form);
      const response = await addLecturer(formData);

      if (!response.success) {
        errorEl.textContent = response.error || 'Error adding lecturer';
        return;
      }

      formContainer.classList.add('hidden');
      form.reset();

      alert(`Lecturer added successfully!\nEmail: ${response.credentials.email}\nPassword: ${response.credentials.password}`);

      loadLecturersList();
      loadDashboardStats();
    } catch (error) {
      errorEl.textContent = 'Error adding lecturer';
      console.error(error);
    }
  });

  loadLecturersList();
}

async function loadLecturersList() {
  try {
    const response = await getLecturers();
    if (!response.success) {
      return;
    }

    const tbody = document.getElementById('lecturers-tbody');
    tbody.innerHTML = '';

    for (const lecturer of response.data) {
      const row = document.createElement('tr');
      const coursesText = lecturer.courses_teaching.join(', ');
      row.innerHTML = `
        <td>${lecturer.name}</td>
        <td>${lecturer.email}</td>
        <td>${coursesText}</td>
        <td>
          <button class="action-btn delete" data-id="${lecturer.id}">Delete</button>
        </td>
      `;

      const deleteBtn = row.querySelector('.delete');
      deleteBtn.addEventListener('click', async () => {
        if (confirm('Are you sure you want to delete this lecturer?')) {
          try {
            await deleteLecturer(lecturer.id);
            loadLecturersList();
            loadDashboardStats();
          } catch (error) {
            console.error(error);
          }
        }
      });

      tbody.appendChild(row);
    }
  } catch (error) {
    console.error('Error loading lecturers:', error);
  }
}

function setupAttendanceReports() {
  const filterBtn = document.getElementById('filter-report-btn');
  const monthSelect = document.getElementById('report-month');

  filterBtn.addEventListener('click', async () => {
    const month = monthSelect.value;
    if (!month) {
      alert('Please select a month');
      return;
    }

    try {
      const year = new Date().getFullYear();
      const response = await getMonthlyStats(month, year);

      if (!response.success) {
        return;
      }

      const tbody = document.getElementById('attendance-tbody');
      tbody.innerHTML = '';

      for (const record of response.data) {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${record.student_name}</td>
          <td>${record.date}</td>
          <td>${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</td>
          <td>${(record.confidence_score * 100).toFixed(1)}%</td>
        `;
        tbody.appendChild(row);
      }
    } catch (error) {
      console.error('Error loading attendance report:', error);
    }
  });
}

function setupNotifications() {
  const sendBtn = document.getElementById('send-notifications-btn');
  const monthSelect = document.getElementById('notification-month');
  const statusEl = document.getElementById('notification-status');

  sendBtn.addEventListener('click', async () => {
    const month = monthSelect.value;
    if (!month) {
      alert('Please select a month');
      return;
    }

    statusEl.textContent = 'Sending notifications...';

    try {
      const year = new Date().getFullYear();
      const response = await sendNotifications(month, year);

      if (response.success) {
        statusEl.textContent = response.message;
        setTimeout(() => {
          statusEl.textContent = '';
        }, 5000);
      } else {
        statusEl.textContent = 'Error sending notifications';
      }
    } catch (error) {
      statusEl.textContent = 'Error sending notifications';
      console.error(error);
    }
  });
}
