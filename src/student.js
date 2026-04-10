import { getCurrentUser, saveSession, handleLogout } from './auth.js';
import { getStudentAttendance } from './api-client.js';

export async function initStudentDashboard() {
  saveSession();
  setupLogout();
  loadStudentStats();
  setupMonthlyAttendanceView();
}

function setupLogout() {
  document.querySelectorAll('#logout-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      handleLogout();
    });
  });
}

async function loadStudentStats() {
  const now = new Date();
  const month = now.getMonth() + 1;
  const year = now.getFullYear();

  try {
    const response = await getStudentAttendance(month, year);
    if (!response.success) {
      return;
    }

    const present = response.data.filter(a => a.status === 'present').length;
    const absent = response.data.filter(a => a.status === 'absent').length;
    const total = response.data.length;

    const attendanceRate = total === 0 ? 0 : Math.round((present / total) * 100);

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

  viewBtn.addEventListener('click', async () => {
    const month = monthSelect.value;
    if (!month) {
      alert('Please select a month');
      return;
    }

    try {
      const year = new Date().getFullYear();
      const response = await getStudentAttendance(month, year);

      if (!response.success) {
        return;
      }

      const tbody = document.getElementById('student-attendance-tbody');
      tbody.innerHTML = '';

      if (response.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No attendance records for this month</td></tr>';
        return;
      }

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
    } catch (error) {
      console.error('Error loading monthly attendance:', error);
    }
  });
}
