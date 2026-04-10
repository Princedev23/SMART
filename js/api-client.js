const API_BASE_URL = '/smart/api';

export async function login(email, password, role) {
  console.log('API login called with:', { email, password, role });
  
  const response = await fetch(`${API_BASE_URL}/auth.php?action=login`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password, role })
  });

  const data = await response.json();
  console.log('API response status:', response.status);
  console.log('API response:', data);
  
  return data;
}

export async function logout() {
  const response = await fetch(`${API_BASE_URL}/auth.php?action=logout`, {
    credentials: 'include'
  });
  return await response.json();
}

export async function checkAuth() {
  const response = await fetch(`${API_BASE_URL}/auth.php?action=check`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function getStudents() {
  const response = await fetch(`${API_BASE_URL}/student.php?action=list`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function addStudent(formData) {
  const response = await fetch(`${API_BASE_URL}/student.php?action=add`, {
    method: 'POST',
    credentials: 'include',
    body: formData
  });

  return await response.json();
}

export async function deleteStudent(id) {
  const response = await fetch(`${API_BASE_URL}/student.php?action=delete&id=${id}`, {
    method: 'GET',
    credentials: 'include'
  });

  return await response.json();
}

export async function getLecturers() {
  const response = await fetch(`${API_BASE_URL}/lecturere.php?action=list`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function addLecturer(formData) {
  const response = await fetch(`${API_BASE_URL}/lecturere.php?action=add`, {
    method: 'POST',
    credentials: 'include',
    body: formData
  });

  return await response.json();
}

export async function deleteLecturer(id) {
  const response = await fetch(`${API_BASE_URL}/lecturere.php?action=delete&id=${id}`, {
    method: 'GET',
    credentials: 'include'
  });

  return await response.json();
}

export async function recordAttendance(data) {
  const response = await fetch(`${API_BASE_URL}/attendance.php?action=record`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data)
  });

  return await response.json();
}

export async function getAttendanceByDate(date) {
  const response = await fetch(`${API_BASE_URL}/attendance.php?action=get_by_date&date=${date}`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function getAttendanceByStudent(studentId, month, year) {
  const response = await fetch(`${API_BASE_URL}/attendance.php?action=get_by_student&student_id=${studentId}&month=${month}&year=${year}`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function updateAttendance(data) {
  const response = await fetch(`${API_BASE_URL}/attendance.php?action=update`, {
    method: 'PUT',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data)
  });

  return await response.json();
}

export async function deleteAttendance(id) {
  const response = await fetch(`${API_BASE_URL}/attendance.php?action=delete&id=${id}`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function getLecturerAttendance() {
  const response = await fetch(`${API_BASE_URL}/attendance.php?action=get_lecturer_attendance`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function getStudentAttendance(month, year) {
  const response = await fetch(`${API_BASE_URL}/attendance.php?action=get_student_attendance&month=${month}&year=${year}`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function getAdminStats() {
  const response = await fetch(`${API_BASE_URL}/admin.php?action=get_stats`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function getMonthlyAttendanceRate(month, year) {
  const response = await fetch(`${API_BASE_URL}/admin.php?action=get_monthly_attendance_rate&month=${month}&year=${year}`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function getAnnualAttendanceRate(year) {
  const response = await fetch(`${API_BASE_URL}/admin.php?action=get_annual_attendance_rate&year=${year}`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function getMonthlyStats(month, year) {
  const response = await fetch(`${API_BASE_URL}/attendance.php?action=get_monthly_stats&month=${month}&year=${year}`, {
    credentials: 'include'
  });

  return await response.json();
}

export async function sendNotifications(month, year) {
  const response = await fetch(`${API_BASE_URL}/admin.php?action=send_notifications`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ month, year })
  });

  return await response.json();
}

export async function getStudentProfile() {
  const response = await fetch(`${API_BASE_URL}/student.php?action=profile`, {
    credentials: 'include'
  });
  return await response.json();
}

export async function getLecturerProfile() {
  const response = await fetch(`${API_BASE_URL}/lecturere.php?action=profile`, {
    credentials: 'include'
  });
  return await response.json();
}

export async function updateStudent(data) {
  const response = await fetch(`${API_BASE_URL}/student.php?action=update`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  return await response.json();
}

export async function updateLecturer(data) {
  const response = await fetch(`${API_BASE_URL}/lecturere.php?action=update`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  return await response.json();
}
