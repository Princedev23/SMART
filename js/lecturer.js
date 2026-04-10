import { getCurrentUser, saveSession } from './auth.js';
import {
  getStudents,
  recordAttendance,
  deleteAttendance,
  updateAttendance,
  getLecturerAttendance
} from './api-client.js';
import { initFaceRecognition, detectFace } from './face-recognition.js';

let stream         = null;
let liveActive     = false;
let scanInterval   = null;
let sessionMode    = 'live';   // 'live' | 'upload'
let sessionStudents = [];
let sessionMarked  = new Set();
let endBtnBound    = false;

export async function initLecturerDashboard() {
  saveSession();
  setupSidebarNav();
  loadLecturerStats();
  setupAttendanceSection();
  loadAttendanceRecords();
}

// ─── Sidebar ──────────────────────────────────────────────────────────────────
function setupSidebarNav() {
  const navLinks = document.querySelectorAll('#lecturer-page .nav-link[data-section]');
  const sections = document.querySelectorAll('#lecturer-page .section');
  navLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const target = link.dataset.section;
      navLinks.forEach(l => l.classList.remove('active'));
      link.classList.add('active');
      sections.forEach(s =>
        s.id === target ? s.classList.add('active') : s.classList.remove('active')
      );
    });
  });
}

// ─── Stats ────────────────────────────────────────────────────────────────────
async function loadLecturerStats() {
  const today = new Date().toISOString().split('T')[0];
  try {
    const res = await getLecturerAttendance();
    if (res.success) {
      const todayRecs = res.data.filter(r => r.date === today);
      const el1 = document.getElementById('attendance-recorded');
      const el2 = document.getElementById('classes-today');
      if (el1) el1.textContent = todayRecs.length;
      if (el2) el2.textContent = todayRecs.length;
    }
  } catch (e) { console.error('Stats error:', e); }
}

// ─── Attendance Section Setup ─────────────────────────────────────────────────
function setupAttendanceSection() {
  // Mode buttons
  document.querySelectorAll('.mode-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      sessionMode = btn.dataset.mode;
    });
  });

  // Start button
  const startBtn = document.getElementById('start-attendance-btn');
  if (startBtn) {
    startBtn.addEventListener('click', handleStartSession);
  }

  // Wire the upload drop-area click to open the file picker
  const dropArea  = document.getElementById('upload-drop-area');
  const fileInput = document.getElementById('attendance-image-input');
  if (dropArea && fileInput) {
    // Clicking anywhere in drop area (not just the label) opens file dialog
    dropArea.addEventListener('click', e => {
      // Don't double-fire if they clicked the label itself
      if (e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
        fileInput.click();
      }
    });
  }
}

// ─── Start Session ────────────────────────────────────────────────────────────
async function handleStartSession() {
  const startBtn     = document.getElementById('start-attendance-btn');
  const modeSelector = document.getElementById('attendance-mode-selector');

  // Load student list first
  const res = await getStudents();
  if (!res.success) {
    alert('Could not load student list. Please try again.');
    return;
  }
  sessionStudents = res.data;
  sessionMarked   = new Set();
  resetLog();

  // Lock UI
  startBtn.disabled = true;
  modeSelector.style.opacity      = '0.5';
  modeSelector.style.pointerEvents = 'none';

  // Show attendance log panel
  document.getElementById('auto-attendance-log').classList.remove('hidden');

  // Bind end-session button only once
  if (!endBtnBound) {
    document.getElementById('end-attendance-btn').addEventListener('click', endSession);
    const endUploadBtn = document.getElementById('end-upload-btn');
    if (endUploadBtn) endUploadBtn.addEventListener('click', endSession);
    endBtnBound = true;
  }

  if (sessionMode === 'live') {
    await startLiveSession();
  } else {
    startUploadSession();
  }
}

// ─── LIVE CAMERA MODE ─────────────────────────────────────────────────────────
async function startLiveSession() {
  const msgEl = document.getElementById('detection-message');

  // Check camera API availability
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    msgEl.textContent = '⚠ Camera API not available. Use HTTPS or try the Upload mode.';
    resetSessionLock();
    return;
  }

  try {
    msgEl.textContent = '⏳ Starting camera…';
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
  } catch (err) {
    console.error('Camera error:', err);
    let hint = 'Cannot access camera.';
    if (err.name === 'NotAllowedError')  hint = '🚫 Camera permission denied. Please allow camera access in your browser settings.';
    if (err.name === 'NotFoundError')    hint = '📷 No camera found on this device.';
    if (err.name === 'NotReadableError') hint = '📷 Camera is in use by another application.';
    if (location.protocol === 'http:')   hint += '\n⚠ Note: Camera requires HTTPS except on localhost.';
    msgEl.textContent = hint;
    resetSessionLock();
    return;
  }

  // Show camera feed
  const video    = document.getElementById('attendance-video');
  const camBox   = document.getElementById('camera-container');
  video.srcObject = stream;
  camBox.classList.remove('hidden');
  liveActive = true;

  // Wait for video to be ready then start scanning
  video.addEventListener('loadedmetadata', async () => {
    const overlay = document.getElementById('scan-overlay');
    if (overlay) overlay.classList.remove('hidden');
    msgEl.textContent = '📷 Camera active — scanning faces…';

    // Load face-recognition models (with a gentle loading indicator)
    try {
      await initFaceRecognition();
    } catch (e) {
      console.warn('Face model load warning (demo mode will still work):', e);
    }

    startAutoScan();
  }, { once: true });

  // If video stalls (common in some browsers), force play
  video.play().catch(() => {});
}

function startAutoScan() {
  const canvas = document.getElementById('attendance-canvas');
  const video  = document.getElementById('attendance-video');
  const msgEl  = document.getElementById('detection-message');

  scanInterval = setInterval(async () => {
    if (!liveActive || !video.videoWidth) return;

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    try {
      const matches = await detectFace(canvas, sessionStudents);
      if (!matches || matches.length === 0) {
        msgEl.textContent = '👀 Scanning — point the camera at a student…';
        return;
      }
      const best = matches[0];
      if (!sessionMarked.has(best.student.id)) {
        msgEl.textContent = `✅ Recognised: ${best.student.name} (${(best.confidence * 100).toFixed(1)}%)`;
        await autoMarkPresent(best.student, best.confidence);
      } else {
        msgEl.textContent = `✔ ${best.student.name} already marked present`;
      }
    } catch (e) { console.error('Scan error:', e); }
  }, 2500);
}

// ─── UPLOAD IMAGE MODE ────────────────────────────────────────────────────────
function startUploadSession() {
  const uploadBox  = document.getElementById('upload-container');
  const fileInput  = document.getElementById('attendance-image-input');
  const dropArea   = document.getElementById('upload-drop-area');
  const preview    = document.getElementById('upload-preview');
  const controls   = document.getElementById('upload-controls');
  const processBtn = document.getElementById('process-image-btn');
  const msgEl      = document.getElementById('upload-detection-message');

  // Show the upload box
  uploadBox.classList.remove('hidden');
  msgEl.textContent = '📂 Select or drag a class photo below, then click Process Image.';

  // File input change
  fileInput.addEventListener('change', e => loadPreview(e.target.files[0]));

  // Drag & drop
  dropArea.addEventListener('dragover', e => { e.preventDefault(); dropArea.classList.add('drag-over'); });
  dropArea.addEventListener('dragleave', ()  => dropArea.classList.remove('drag-over'));
  dropArea.addEventListener('drop', e => {
    e.preventDefault();
    dropArea.classList.remove('drag-over');
    const f = e.dataTransfer.files[0];
    if (f && f.type.startsWith('image/')) loadPreview(f);
  });

  function loadPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      preview.src = ev.target.result;
      preview.classList.remove('hidden');
      controls.style.display = 'flex';
      msgEl.textContent = '✅ Image loaded — click "Process Image" to detect faces.';
    };
    reader.readAsDataURL(file);
  }

  // Process button
  processBtn.addEventListener('click', processUploadedImage);
}

async function processUploadedImage() {
  const preview    = document.getElementById('upload-preview');
  const msgEl      = document.getElementById('upload-detection-message');
  const processBtn = document.getElementById('process-image-btn');

  if (!preview.src || preview.classList.contains('hidden')) {
    msgEl.textContent = '⚠ Please select an image first.';
    return;
  }

  processBtn.disabled = true;
  processBtn.textContent = '⏳ Processing…';
  msgEl.textContent = '🔍 Analysing image, please wait…';

  try {
    // Load models if not already loaded
    try { await initFaceRecognition(); } catch (e) { /* demo mode fallback */ }

    const img = new Image();
    img.src = preview.src;
    await new Promise(resolve => { img.onload = resolve; });

    const canvas = document.createElement('canvas');
    canvas.width  = img.naturalWidth;
    canvas.height = img.naturalHeight;
    canvas.getContext('2d').drawImage(img, 0, 0);

    const matches = await detectFace(canvas, sessionStudents);

    if (!matches || matches.length === 0) {
      msgEl.textContent = '❌ No registered faces detected in this image.';
    } else {
      msgEl.textContent = `✅ Found ${matches.length} match(es) — recording attendance…`;
      for (const m of matches) {
        if (!sessionMarked.has(m.student.id)) {
          await autoMarkPresent(m.student, m.confidence);
        }
      }
    }

    await markAbsentees();
    msgEl.textContent = `✅ Done! ${sessionMarked.size} present, ${sessionStudents.length - sessionMarked.size} absent.`;

  } catch (err) {
    console.error('Image processing error:', err);
    msgEl.textContent = '⚠ Error processing image. Please try again.';
  }

  processBtn.disabled = false;
  processBtn.textContent = '🔍 Process Image';
}

// ─── Mark Present / Absent ────────────────────────────────────────────────────
async function autoMarkPresent(student, confidence) {
  const today = new Date().toISOString().split('T')[0];
  try {
    await recordAttendance({ student_id: student.id, date: today, status: 'present', confidence_score: confidence });
    sessionMarked.add(student.id);
    addLogEntry(student.name, 'present', confidence);
    loadLecturerStats();
    loadAttendanceRecords();
  } catch (e) { console.error('Mark present error:', e); }
}

async function markAbsentees() {
  const today = new Date().toISOString().split('T')[0];
  for (const student of sessionStudents) {
    if (sessionMarked.has(student.id)) continue;
    try {
      await recordAttendance({ student_id: student.id, date: today, status: 'absent', confidence_score: 0 });
      addLogEntry(student.name, 'absent', 0);
    } catch (e) { console.error('Mark absent error:', e); }
  }
  loadLecturerStats();
  loadAttendanceRecords();
}

// ─── End Session ──────────────────────────────────────────────────────────────
async function endSession() {
  liveActive = false;
  if (scanInterval)  { clearInterval(scanInterval); scanInterval = null; }
  if (stream)        { stream.getTracks().forEach(t => t.stop()); stream = null; }

  // Mark remaining students absent for live mode
  if (sessionStudents.length > 0 && sessionMode === 'live') {
    const msgEl = document.getElementById('detection-message');
    if (msgEl) msgEl.textContent = '⏳ Marking remaining students absent…';
    await markAbsentees();
  }

  // Hide containers
  const hide = id => { const el = document.getElementById(id); if (el) el.classList.add('hidden'); };
  hide('camera-container');
  hide('upload-container');
  hide('scan-overlay');

  // Clear messages
  const dm = document.getElementById('detection-message');
  const um = document.getElementById('upload-detection-message');
  const up = document.getElementById('upload-preview');
  const uc = document.getElementById('upload-controls');
  if (dm) dm.textContent = '';
  if (um) um.textContent = '';
  if (up) { up.src = ''; up.classList.add('hidden'); }
  if (uc) uc.style.display = 'none';

  // Reset file input so the same file can be re-selected
  const fi = document.getElementById('attendance-image-input');
  if (fi) fi.value = '';

  resetSessionLock();
  sessionStudents = [];
  sessionMarked   = new Set();
}

function resetSessionLock() {
  const startBtn     = document.getElementById('start-attendance-btn');
  const modeSelector = document.getElementById('attendance-mode-selector');
  if (startBtn)     startBtn.disabled = false;
  if (modeSelector) { modeSelector.style.opacity = ''; modeSelector.style.pointerEvents = ''; }
}

// ─── Attendance Log ───────────────────────────────────────────────────────────
function resetLog() {
  const entries  = document.getElementById('log-entries');
  const pCount   = document.getElementById('log-present-count');
  const aCount   = document.getElementById('log-absent-count');
  if (entries) entries.innerHTML = '';
  if (pCount)  pCount.textContent  = '✅ Present: 0';
  if (aCount)  aCount.textContent  = '❌ Absent: 0';
}

function addLogEntry(name, status, confidence) {
  const container = document.getElementById('log-entries');
  if (!container) return;
  const entry = document.createElement('div');
  entry.className = `log-entry log-${status}`;
  const confText = status === 'present' ? ` — ${(confidence * 100).toFixed(1)}% conf` : '';
  entry.innerHTML = `
    <span class="log-status-icon">${status === 'present' ? '✅' : '❌'}</span>
    <span class="log-name">${name}</span>
    <span class="log-conf">${confText}</span>
    <span class="log-time">${new Date().toLocaleTimeString()}</span>
  `;
  container.prepend(entry);

  const all = container.querySelectorAll('.log-entry');
  let p = 0, a = 0;
  all.forEach(e => e.classList.contains('log-present') ? p++ : a++);
  const pc = document.getElementById('log-present-count');
  const ac = document.getElementById('log-absent-count');
  if (pc) pc.textContent = `✅ Present: ${p}`;
  if (ac) ac.textContent = `❌ Absent: ${a}`;
}

// ─── Attendance Records Table ─────────────────────────────────────────────────
async function loadAttendanceRecords() {
  try {
    const res = await getLecturerAttendance();
    if (!res.success) return;

    const tbody = document.getElementById('lecturer-attendance-tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    for (const record of res.data) {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${record.student_name}</td>
        <td>${record.date}</td>
        <td><span class="status-badge status-${record.status}">${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</span></td>
        <td>
          <button class="action-btn edit" data-id="${record.id}">
            ${record.status === 'present' ? 'Mark Absent' : 'Mark Present'}
          </button>
          <button class="action-btn delete" data-id="${record.id}">Delete</button>
        </td>
      `;
      row.querySelector('.edit').addEventListener('click', async () => {
        const newStatus = record.status === 'present' ? 'absent' : 'present';
        await updateAttendance({ id: record.id, status: newStatus });
        loadAttendanceRecords();
      });
      row.querySelector('.delete').addEventListener('click', async () => {
        if (confirm('Delete this attendance record?')) {
          await deleteAttendance(record.id);
          loadAttendanceRecords();
        }
      });
      tbody.appendChild(row);
    }
  } catch (e) { console.error('Error loading attendance records:', e); }
}
