import { getCurrentUser, saveSession, handleLogout } from './auth.js';
import {
  getStudents,
  addStudent,
  deleteStudent,
  updateStudent,
  getLecturers,
  addLecturer,
  deleteLecturer,
  updateLecturer,
  getMonthlyStats,
  getAdminStats,
  getMonthlyAttendanceRate,
  getAnnualAttendanceRate,
  sendNotifications
} from './api-client.js';
import { initFaceRecognition, captureFaceDescriptorFromCanvas } from './face-recognition.js';

export async function initAdminDashboard() {
  saveSession();
  setupSidebarNav();
  loadDashboardStats();
  setupStudentManagement();
  setupLecturerManagement();
  setupAttendanceReports();
  setupNotifications();
  loadAdminProfile();
  setupEditStudentModal();
  setupEditLecturerModal();
}


function setupSidebarNav() {
  const navLinks = document.querySelectorAll('#admin-page .nav-link[data-section]');
  const sections = document.querySelectorAll('#admin-page .section');

  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetSection = link.dataset.section;
      navLinks.forEach(l => l.classList.remove('active'));
      link.classList.add('active');
      sections.forEach(s => {
        s.id === targetSection ? s.classList.add('active') : s.classList.remove('active');
      });
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

  addBtn.addEventListener('click', () => formContainer.classList.remove('hidden'));

  cancelBtn.addEventListener('click', () => {
    formContainer.classList.add('hidden');
    form.reset();
    clearFieldErrors();
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
    clearFieldErrors();

    if (!validateStudentForm()) {
      errorEl.textContent = 'Please fix the errors above before submitting.';
      errorEl.style.color = 'red';
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.textContent = 'Analysing face...';
    submitBtn.disabled = true;

    try {
      // ── REAL FACE DESCRIPTOR EXTRACTION ──────────────────────────────
      const faceFile = document.getElementById('student-face').files[0];
      let faceDescriptorJSON = null;

      if (faceFile) {
        try {
          await initFaceRecognition();

          // Draw the uploaded photo onto a canvas then extract the descriptor
          const img = await createImageBitmap(faceFile);
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          canvas.getContext('2d').drawImage(img, 0, 0);

          const descriptor = await captureFaceDescriptorFromCanvas(canvas);

          if (!descriptor) {
            errorEl.textContent = 'No face detected in the uploaded photo. Please use a clear, well-lit face image and try again.';
            errorEl.style.color = 'red';
            submitBtn.textContent = 'Add Student';
            submitBtn.disabled = false;
            return;
          }

          // Convert Float32Array to a plain JSON array for storage
          faceDescriptorJSON = JSON.stringify(Array.from(descriptor));
          console.log('Face descriptor extracted successfully');

        } catch (faceErr) {
          console.error('Face extraction error:', faceErr);
          // Don't block registration if models fail to load — warn and continue
          errorEl.textContent = 'Warning: Face models could not load. Student will be registered without face recognition capability.';
          errorEl.style.color = 'orange';
        }
      }
      // ─────────────────────────────────────────────────────────────────

      submitBtn.textContent = 'Adding...';
      const formData = new FormData(form);

      // Append the face descriptor so PHP can store it
      if (faceDescriptorJSON) {
        formData.append('face_descriptor', faceDescriptorJSON);
      }

      const response = await addStudent(formData);

      if (!response.success) {
        errorEl.textContent = response.error || 'Error adding student';
        errorEl.style.color = 'red';
        submitBtn.textContent = 'Add Student';
        submitBtn.disabled = false;
        return;
      }

      formContainer.classList.add('hidden');
      form.reset();
      document.getElementById('face-preview').classList.add('hidden');
      submitBtn.textContent = 'Add Student';
      submitBtn.disabled = false;

      showSuccessAlert(`Student registered successfully!\nEmail: ${response.credentials.email}\nPassword: ${response.credentials.password}`);

      loadStudentsList();
      loadDashboardStats();

    } catch (error) {
      errorEl.textContent = 'Error adding student. Please try again.';
      errorEl.style.color = 'red';
      submitBtn.textContent = 'Add Student';
      submitBtn.disabled = false;
      console.error(error);
    }
  });

  loadStudentsList();
}

async function loadStudentsList() {
  try {
    const response = await getStudents();
    if (!response.success) return;

    const tbody = document.getElementById('students-tbody');
    tbody.innerHTML = '';

    for (const student of response.data) {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${student.name}</td>
        <td>${student.email}</td>
        <td>${student.department}</td>
        <td>${student.gender || '-'}</td>
        <td>${student.phone_number || '-'}</td>
        <td>${student.parent_phone || '-'}</td>
        <td>${student.guardian_email}</td>
        <td>
          <button class="action-btn edit" data-id="${student.id}">✏️ Edit</button>
          <button class="action-btn delete" data-id="${student.id}">🗑️ Delete</button>
        </td>
      `;

      row.querySelector('.edit').addEventListener('click', () => {
        document.getElementById('edit-student-id').value = student.id;
        document.getElementById('edit-student-name').value = student.name;
        document.getElementById('edit-student-department').value = student.department;
        document.getElementById('edit-student-gender').value = student.gender || '';
        document.getElementById('edit-student-phone').value = student.phone_number || '';
        document.getElementById('edit-student-parent-phone').value = student.parent_phone || '';
        document.getElementById('edit-student-guardian-email').value = student.guardian_email;
        document.getElementById('edit-student-modal').classList.remove('hidden');
      });

      row.querySelector('.delete').addEventListener('click', async () => {
        if (confirm('Are you sure you want to delete this student?')) {
          try {
            await deleteStudent(student.id);
            loadStudentsList();
            loadDashboardStats();
          } catch (error) { console.error(error); }
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
  const imageInput = document.getElementById('lecturer-image');

  addBtn.addEventListener('click', () => formContainer.classList.remove('hidden'));

  cancelBtn.addEventListener('click', () => {
    formContainer.classList.add('hidden');
    form.reset();
    clearFieldErrors();
  });

  imageInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (event) => {
        const preview = document.getElementById('lecturer-preview');
        preview.src = event.target.result;
        preview.classList.remove('hidden');
      };
      reader.readAsDataURL(file);
    }
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('lecturer-form-error');
    errorEl.textContent = '';
    clearFieldErrors();

    if (!validateLecturerForm()) {
      errorEl.textContent = 'Please fix the errors above before submitting.';
      errorEl.style.color = 'red';
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.textContent = 'Adding...';
    submitBtn.disabled = true;

    try {
      const formData = new FormData(form);
      const response = await addLecturer(formData);

      if (!response.success) {
        errorEl.textContent = response.error || 'Error adding lecturer';
        errorEl.style.color = 'red';
        submitBtn.textContent = 'Add Lecturer';
        submitBtn.disabled = false;
        return;
      }

      formContainer.classList.add('hidden');
      form.reset();
      submitBtn.textContent = 'Add Lecturer';
      submitBtn.disabled = false;

      showSuccessAlert(`Lecturer registered successfully!\nEmail: ${response.credentials.email}\nPassword: ${response.credentials.password}`);

      loadLecturersList();
      loadDashboardStats();
    } catch (error) {
      errorEl.textContent = 'Error adding lecturer. Please try again.';
      errorEl.style.color = 'red';
      submitBtn.textContent = 'Add Lecturer';
      submitBtn.disabled = false;
      console.error(error);
    }
  });

  loadLecturersList();
}

async function loadLecturersList() {
  try {
    const response = await getLecturers();
    if (!response.success) return;

    const tbody = document.getElementById('lecturers-tbody');
    tbody.innerHTML = '';

    for (const lecturer of response.data) {
      const row = document.createElement('tr');
      const coursesText = lecturer.courses_teaching.join(', ');
      row.innerHTML = `
        <td>${lecturer.name}</td>
        <td>${lecturer.email}</td>
        <td>${lecturer.gender || '-'}</td>
        <td>${coursesText}</td>
        <td>
          <button class="action-btn edit" data-id="${lecturer.id}">✏️ Edit</button>
          <button class="action-btn delete" data-id="${lecturer.id}">🗑️ Delete</button>
        </td>
      `;

      row.querySelector('.edit').addEventListener('click', () => {
        document.getElementById('edit-lecturer-id').value = lecturer.id;
        document.getElementById('edit-lecturer-name').value = lecturer.name;
        document.getElementById('edit-lecturer-gender').value = lecturer.gender || '';
        document.getElementById('edit-lecturer-phone').value = lecturer.phone_number || '';
        document.getElementById('edit-lecturer-courses').value = lecturer.courses_teaching.join(', ');
        document.getElementById('edit-lecturer-modal').classList.remove('hidden');
      });

      row.querySelector('.delete').addEventListener('click', async () => {
        if (confirm('Are you sure you want to delete this lecturer?')) {
          try {
            await deleteLecturer(lecturer.id);
            loadLecturersList();
            loadDashboardStats();
          } catch (error) { console.error(error); }
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
  if (!filterBtn || !monthSelect) return;

  filterBtn.addEventListener('click', async () => {
    const month = monthSelect.value;
    if (!month) { alert('Please select a month'); return; }

    try {
      const year = new Date().getFullYear();
      const response = await getMonthlyStats(month, year);
      if (!response.success) return;

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
    if (!month) { alert('Please select a month'); return; }

    statusEl.textContent = 'Sending notifications...';

    try {
      const year = new Date().getFullYear();
      const response = await sendNotifications(month, year);
      if (response.success) {
        statusEl.textContent = response.message;
        setTimeout(() => { statusEl.textContent = ''; }, 5000);
      } else {
        statusEl.textContent = 'Error sending notifications';
      }
    } catch (error) {
      statusEl.textContent = 'Error sending notifications';
      console.error(error);
    }
  });
}

function validateStudentForm() {
  let isValid = true;
  const name = document.getElementById('student-name').value.trim();
  const email = document.getElementById('student-email').value.trim();
  const department = document.getElementById('student-department').value;
  const gender = document.getElementById('student-gender').value;
  const phone = document.getElementById('student-phone').value.trim();
  const parentPhone = document.getElementById('parent-phone').value.trim();
  const guardianEmail = document.getElementById('guardian-email').value.trim();
  const faceImage = document.getElementById('student-face').files[0];

  if (!name) { showFieldError('student-name', 'Student name is required'); isValid = false; }
  else if (name.length < 2) { showFieldError('student-name', 'Name must be at least 2 characters'); isValid = false; }

  if (!email) { showFieldError('student-email', 'Student email is required'); isValid = false; }
  else if (!isValidEmail(email)) { showFieldError('student-email', 'Please enter a valid email address'); isValid = false; }

  if (!department) { showFieldError('student-department', 'Please select a department'); isValid = false; }

  if (!gender) { showFieldError('student-gender', 'Please select a gender'); isValid = false; }

  if (!phone) { showFieldError('student-phone', 'Student phone number is required'); isValid = false; }
  else if (!/^\d{9,15}$/.test(phone.replace(/\s/g, ''))) { showFieldError('student-phone', 'Phone must be 9-15 digits'); isValid = false; }

  if (!parentPhone) { showFieldError('parent-phone', 'Parent phone number is required'); isValid = false; }
  else if (!/^\d{9,15}$/.test(parentPhone.replace(/\s/g, ''))) { showFieldError('parent-phone', 'Phone must be 9-15 digits'); isValid = false; }

  if (!guardianEmail) { showFieldError('guardian-email', 'Guardian email is required'); isValid = false; }
  else if (!isValidEmail(guardianEmail)) { showFieldError('guardian-email', 'Please enter a valid email address'); isValid = false; }

  if (!faceImage) { showFieldError('student-face', 'Student face image is required'); isValid = false; }

  return isValid;
}

function validateLecturerForm() {
  let isValid = true;
  const name = document.getElementById('lecturer-name').value.trim();
  const email = document.getElementById('lecturer-email').value.trim();
  const phone = document.getElementById('lecturer-phone').value.trim();
  const courses = document.getElementById('lecturer-courses').value;
  const gender = document.getElementById('lecturer-gender').value;
  const lecturerImage = document.getElementById('lecturer-image').files[0];

  if (!name) { showFieldError('lecturer-name', 'Lecturer name is required'); isValid = false; }
  else if (name.length < 2) { showFieldError('lecturer-name', 'Name must be at least 2 characters'); isValid = false; }

  if (!email) { showFieldError('lecturer-email', 'Lecturer email is required'); isValid = false; }
  else if (!isValidEmail(email)) { showFieldError('lecturer-email', 'Please enter a valid email address'); isValid = false; }

  if (!phone) { showFieldError('lecturer-phone', 'Lecturer phone number is required'); isValid = false; }
  else if (!/^\d{9,15}$/.test(phone.replace(/\s/g, ''))) { showFieldError('lecturer-phone', 'Phone must be 9-15 digits'); isValid = false; }

  if (!courses) { showFieldError('lecturer-courses', 'Please select a course'); isValid = false; }

  if (!gender) { showFieldError('lecturer-gender', 'Please select a gender'); isValid = false; }

  if (!lecturerImage) { showFieldError('lecturer-image', 'Lecturer image is required'); isValid = false; }

  return isValid;
}

function showFieldError(fieldId, message) {
  const errorEl = document.getElementById(`${fieldId}-error`);
  if (errorEl) errorEl.textContent = message;
}

function clearFieldErrors() {
  document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function loadAdminProfile() {
  const session = JSON.parse(sessionStorage.getItem('userSession') || localStorage.getItem('userSession') || '{}');
  const emailEl = document.getElementById('ap-email');
  if (emailEl && session.user) emailEl.textContent = session.user.email || '—';
}

function showSuccessAlert(message) {
  const existing = document.getElementById('success-alert');
  if (existing) existing.remove();

  const alert = document.createElement('div');
  alert.id = 'success-alert';
  alert.style.cssText = `
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    background: #16a34a; color: white; padding: 16px 24px;
    border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    font-size: 14px; line-height: 1.6; max-width: 350px;
    white-space: pre-line; animation: slideInRight 0.3s ease;
  `;
  alert.innerHTML = `<strong>✓ Success</strong><br>${message}`;
  document.body.appendChild(alert);

  setTimeout(() => {
    alert.style.opacity = '0';
    alert.style.transition = 'opacity 0.5s';
    setTimeout(() => alert.remove(), 500);
  }, 5000);
}

function setupEditStudentModal() {
  document.getElementById('cancel-edit-student-btn').addEventListener('click', () => {
    document.getElementById('edit-student-modal').classList.add('hidden');
  });

  document.getElementById('edit-student-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('edit-student-error');
    errorEl.textContent = '';
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    try {
      const response = await updateStudent({
        id: document.getElementById('edit-student-id').value,
        name: document.getElementById('edit-student-name').value,
        department: document.getElementById('edit-student-department').value,
        gender: document.getElementById('edit-student-gender').value,
        phone_number: document.getElementById('edit-student-phone').value,
        parent_phone: document.getElementById('edit-student-parent-phone').value,
        guardian_email: document.getElementById('edit-student-guardian-email').value,
      });
      if (!response.success) {
        errorEl.textContent = response.error || 'Failed to update';
        submitBtn.textContent = 'Save Changes';
        submitBtn.disabled = false;
        return;
      }
      document.getElementById('edit-student-modal').classList.add('hidden');
      loadStudentsList();
      showSuccessAlert('Student updated successfully!');
    } catch (err) {
      errorEl.textContent = 'Error saving changes';
      submitBtn.textContent = 'Save Changes';
      submitBtn.disabled = false;
    }
  });
}

function setupEditLecturerModal() {
  document.getElementById('cancel-edit-lecturer-btn').addEventListener('click', () => {
    document.getElementById('edit-lecturer-modal').classList.add('hidden');
  });

  document.getElementById('edit-lecturer-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('edit-lecturer-error');
    errorEl.textContent = '';
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    try {
      const courses = document.getElementById('edit-lecturer-courses').value
        .split(',').map(c => c.trim()).filter(Boolean);
      const response = await updateLecturer({
        id: document.getElementById('edit-lecturer-id').value,
        name: document.getElementById('edit-lecturer-name').value,
        gender: document.getElementById('edit-lecturer-gender').value,
        phone_number: document.getElementById('edit-lecturer-phone').value,
        courses,
      });
      if (!response.success) {
        errorEl.textContent = response.error || 'Failed to update';
        submitBtn.textContent = 'Save Changes';
        submitBtn.disabled = false;
        return;
      }
      document.getElementById('edit-lecturer-modal').classList.add('hidden');
      loadLecturersList();
      showSuccessAlert('Lecturer updated successfully!');
    } catch (err) {
      errorEl.textContent = 'Error saving changes';
      submitBtn.textContent = 'Save Changes';
      submitBtn.disabled = false;
    }
  });
}
