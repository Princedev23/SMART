import { getCurrentUser, saveSession, handleLogout } from './auth.js';
import {
  getStudents,
  recordAttendance,
  deleteAttendance,
  updateAttendance,
  getLecturerAttendance
} from './api-client.js';
import { initFaceRecognition, detectFace } from './facial-recognition.js';

let stream = null;

export async function initLecturerDashboard() {
  saveSession();
  setupLogout();
  loadLecturerStats();
  setupAttendanceSession();
  loadAttendanceRecords();
}

function setupLogout() {
  document.querySelectorAll('#logout-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      handleLogout();
    });
  });
}

async function loadLecturerStats() {
  const today = new Date().toISOString().split('T')[0];

  try {
    const response = await getLecturerAttendance();
    if (response.success) {
      const todayRecords = response.data.filter(r => r.date === today);
      document.getElementById('attendance-recorded').textContent = todayRecords.length;
    }
  } catch (error) {
    console.error('Error loading stats:', error);
  }
}

function setupAttendanceSession() {
  const startBtn = document.getElementById('start-attendance-btn');
  const cameraContainer = document.getElementById('camera-container');
  const captureBtn = document.getElementById('capture-face-btn');
  const endBtn = document.getElementById('end-attendance-btn');
  const video = document.getElementById('attendance-video');
  const canvas = document.getElementById('attendance-canvas');

  startBtn.addEventListener('click', async () => {
    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user' }
      });

      video.srcObject = stream;
      cameraContainer.classList.remove('hidden');
      startBtn.disabled = true;

      await initFaceRecognition();
    } catch (error) {
      console.error('Error accessing camera:', error);
      alert('Cannot access camera. Please allow camera permissions.');
    }
  });

  captureBtn.addEventListener('click', async () => {
    const detectionMessage = document.getElementById('detection-message');
    const matchedStudents = document.getElementById('matched-students');

    detectionMessage.textContent = 'Processing...';
    matchedStudents.innerHTML = '';

    try {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0);

      const response = await getStudents();
      if (!response.success) {
        detectionMessage.textContent = 'Error loading students';
        return;
      }

      const matches = await detectFace(canvas, response.data);

      if (matches.length === 0) {
        detectionMessage.textContent = 'No matching faces found';
        return;
      }

      detectionMessage.textContent = `Found ${matches.length} match(es)`;
      matchedStudents.innerHTML = '';

      for (const match of matches) {
        const div = document.createElement('div');
        div.className = 'matched-student';
        div.innerHTML = `
          <div class="matched-student-name">${match.student.name}</div>
          <div class="matched-student-confidence">Confidence: ${(match.confidence * 100).toFixed(1)}%</div>
          <button class="action-btn edit" data-student-id="${match.student.id}">Mark Present</button>
        `;

        const markBtn = div.querySelector('.edit');
        markBtn.addEventListener('click', async () => {
          try {
            const today = new Date().toISOString().split('T')[0];

            const attendanceData = {
              student_id: match.student.id,
              date: today,
              status: 'present',
              confidence_score: match.confidence
            };

            await recordAttendance(attendanceData);

            markBtn.disabled = true;
            markBtn.textContent = 'Marked';
            loadLecturerStats();
            loadAttendanceRecords();
          } catch (error) {
            console.error('Error recording attendance:', error);
          }
        });

        matchedStudents.appendChild(div);
      }
    } catch (error) {
      detectionMessage.textContent = 'Error processing face';
      console.error(error);
    }
  });

  endBtn.addEventListener('click', () => {
    if (stream) {
      stream.getTracks().forEach(track => track.stop());
    }
    cameraContainer.classList.add('hidden');
    startBtn.disabled = false;
    document.getElementById('detection-message').textContent = '';
    document.getElementById('matched-students').innerHTML = '';
  });
}

async function loadAttendanceRecords() {
  try {
    const response = await getLecturerAttendance();
    if (!response.success) {
      return;
    }

    const tbody = document.getElementById('lecturer-attendance-tbody');
    tbody.innerHTML = '';

    for (const record of response.data) {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${record.student_name}</td>
        <td>${record.date}</td>
        <td>${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</td>
        <td>
          <button class="action-btn edit" data-id="${record.id}" data-status="${record.status}">
            ${record.status === 'present' ? 'Mark Absent' : 'Mark Present'}
          </button>
          <button class="action-btn delete" data-id="${record.id}">Delete</button>
        </td>
      `;

      const editBtn = row.querySelector('.edit');
      const deleteBtn = row.querySelector('.delete');

      editBtn.addEventListener('click', async () => {
        try {
          const newStatus = record.status === 'present' ? 'absent' : 'present';
          await updateAttendance({ id: record.id, status: newStatus });
          loadAttendanceRecords();
        } catch (error) {
          console.error('Error updating attendance:', error);
        }
      });

      deleteBtn.addEventListener('click', async () => {
        if (confirm('Are you sure you want to delete this attendance record?')) {
          try {
            await deleteAttendance(record.id);
            loadAttendanceRecords();
          } catch (error) {
            console.error('Error deleting attendance:', error);
          }
        }
      });

      tbody.appendChild(row);
    }
  } catch (error) {
    console.error('Error loading attendance records:', error);
  }
}