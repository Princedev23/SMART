import { getCurrentUser, saveSession, handleLogout } from './auth.js';
import { getStudentAttendance, getStudentProfile } from './api-client.js';

export async function initStudentDashboard() {
  saveSession();
  setupSidebarNav();
  loadStudentStats();
  setupMonthlyAttendanceView();
  loadStudentProfile();
}

function setupSidebarNav() {
  const navLinks = document.querySelectorAll('#student-page .nav-link[data-section]');
  const sections = document.querySelectorAll('#student-page .section');

  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetSection = link.dataset.section;

      navLinks.forEach(l => l.classList.remove('active'));
      link.classList.add('active');

      sections.forEach(s => {
        if (s.id === targetSection) {
          s.classList.add('active');
        } else {
          s.classList.remove('active');
        }
      });
    });
  });
}

/*function setupLogout() {
  document.querySelectorAll('#logout-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      handleLogout();
    });
  });
}*/

async function loadStudentStats() {
  const now = new Date();
  const month = now.getMonth() + 1;
  const year = now.getFullYear();

  try {
    //console.log('📊 Loading stats for month:', month, 'year:', year);
    const response = await getStudentAttendance(month, year);
    //console.log('📊 API Response received:', response); 
    if (!response.success) {
       //console.error('❌ API returned error:', response.error);
      return;
    }

    const present = response.data.filter(a => a.status === 'present').length;
    const absent = response.data.filter(a => a.status === 'absent').length;
    const total = response.data.length;

    const attendanceRate = total === 0 ? 0 : Math.round((present / total) * 100);
//console.log('✅ Stats calculated - Present:', present, 'Absent:', absent, 'Total:', total);
    document.getElementById('student-monthly').textContent = `${attendanceRate}%`;
    document.getElementById('classes-attended').textContent = present;
    document.getElementById('classes-missed').textContent = absent;
  } catch (error) {
    console.error('Error loading student stats:', error);
  }
}

function setupMonthlyAttendanceView() {
  const viewBtn = document.getElementById('view-month-btn');
  const monthSelect = document.getElementById('student-month');

  //console.log('📅 Setting up monthly attendance view');
  viewBtn.addEventListener('click', async () => {
    const month = monthSelect.value;
    if (!month) {
      alert('Please select a month');
      return;
    }
//console.log('📅 User clicked View - Loading month:', month);
    try {
      const year = new Date().getFullYear();
      const response = await getStudentAttendance(month, year);
 //console.log('📅 API Response for month attendance:', response);
      if (!response.success) {
         //console.error('❌ API error:', response.error);
          alert('Failed to load attendance: ' + (response.error || 'Unknown error'));
        return;
      }

      const tbody = document.getElementById('student-attendance-tbody');
      tbody.innerHTML = '';

      if (response.data.length === 0) {
        // console.log('ℹ️ No attendance records for this month'); 
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No attendance records for this month</td></tr>';
        return;
      }
//console.log('📅 Found', response.data.length, 'attendance records'); 
      for (const record of response.data) {
        const row = document.createElement('tr');
        const statusClass = record.status === 'present' ? 'status-present' : 'status-absent';
        const statusText = record.status.charAt(0).toUpperCase() + record.status.slice(1);

        const courses = Array.isArray(record.courses_teaching)
          ? record.courses_teaching.join(', ')
          : 'General';

        row.innerHTML = `
          <td>${record.date}</td>
          <td><span class="${statusClass}">${statusText}</span></td>
          <td>${courses}</td>
        `;
        tbody.appendChild(row);
      }
      //console.log('✅ Attendance records loaded successfully');
    } catch (error) {
      //console.error('Error loading monthly attendance:', error);
    }
  });
}

async function loadStudentProfile() {
  try {
    const response = await getStudentProfile();
    if (!response.success) return;
    const d = response.data;

    document.getElementById('sp-name').textContent          = d.name          || '—';
    document.getElementById('sp-email').textContent         = d.email         || '—';
    document.getElementById('sp-department').textContent    = d.department    || '—';
    document.getElementById('sp-phone').textContent         = d.phone_number  || '—';
    document.getElementById('sp-parent-phone').textContent  = d.parent_phone  || '—';
    document.getElementById('sp-guardian-email').textContent= d.guardian_email|| '—';
    document.getElementById('sp-created').textContent       = d.created_at ? d.created_at.split(' ')[0] : '—';

    if (d.face_image_path) {
      const img = document.getElementById('student-profile-img');
      img.src = '/' + d.face_image_path;
      img.classList.remove('hidden');
      document.getElementById('student-no-img').classList.add('hidden');
    }
  } catch (error) {
    console.error('Error loading student profile:', error);
  }
}
