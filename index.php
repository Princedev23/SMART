<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Attendance System</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(-30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4); }
      50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(102, 126, 234, 0); }
    }
  </style>
</head>
<body>
  <div id="app">
    <!-- WELCOME PAGE -->
    <div id="welcome-page" class="page active">
      <div class="welcome-container">
        <div class="welcome-content">
          <div class="welcome-logo">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2L2 7l10 5 10-5-10-5z"/>
              <path d="M2 17l10 5 10-5"/>
              <path d="M2 12l10 5 10-5"/>
            </svg>
          </div>
          <h1>Smart Attendance System</h1>
          <p class="welcome-tagline">Welcome to the Future of Attendance Tracking</p>
          <p class="welcome-description">
            Experience seamless attendance management with face recognition technology. 
            Track, manage, and monitor attendance effortlessly.
          </p>
          <div class="welcome-features">
            <div class="feature">
              <span class="feature-icon">✓</span>
              <span>Face Recognition Attendance</span>
            </div>
            <div class="feature">
              <span class="feature-icon">✓</span>
              <span>Real-time Tracking</span>
            </div>
            <div class="feature">
              <span class="feature-icon">✓</span>
              <span>Guardian Notifications</span>
            </div>
          </div>
          <button id="get-started-btn" class="btn-get-started">Get Started</button>
        </div>
      </div>
    </div>

    <!-- LOGIN PAGE -->
    <div id="login-page" class="page">
      <div class="login-container">
        <div class="login-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);">
          <h1 style="font-size: 22px; margin-bottom: 20px; text-align: center; color: #1e293b;">Smart Attendance</h1>
          <div class="role-selector">
            <button class="role-btn active" data-role="student">Student</button>
            <button class="role-btn" data-role="lecturer">Lecturer</button>
            <button class="role-btn" data-role="admin">Admin</button>
          </div>
          <form id="login-form">
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Login</button>
            <p id="login-error" class="error-message"></p>
          </form>
          
          <!-- Demo Credentials Info
          <div class="demo-info" style="margin-top:16px;padding:12px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;font-size:13px;color:#0369a1;">
            <p style="font-weight:700;margin:0 0 6px;">🎓 Demo Credentials (password: <code>demo123</code>)</p>
            <p style="margin:2px 0;">👩‍🎓 Student: <strong>student@school.edu</strong></p>
            <p style="margin:2px 0;">👩‍🏫 Lecturer: <strong>lecturer@school.edu</strong></p>
            <p style="margin:2px 0;">🛡️ Admin: <strong>admin@school.edu</strong></p>
          </div> -->
        </div>
      </div>
    </div>

    <!-- ADMIN PAGE -->
    <div id="admin-page" class="page">
      <button class="hamburger-btn" onclick="toggleSidebar(this)">
        <span></span><span></span><span></span>
      </button>
      <div class="sidebar-overlay" onclick="closeSidebar(this)"></div>
      <div class="sidebar">
        <div class="sidebar-header">
          <h2>Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
          <a href="#" data-section="dashboard" class="nav-link active">Dashboard</a>
          <a href="#" data-section="students" class="nav-link">Manage Students</a>
          <a href="#" data-section="lecturers" class="nav-link">Manage Lecturers</a>
          <a href="#" data-section="attendance" class="nav-link">Attendance Reports</a>
          <a href="#" data-section="notifications" class="nav-link">Send Notifications</a>
          <a href="#" data-section="admin-profile" class="nav-link">My Profile</a>
          <button type="button" id="logout-btn" class="nav-link">Logout</button>
        </nav>
      </div>
      <div class="main-content">
        <div class="section active" id="dashboard">
          <h2>Dashboard</h2>
          <div class="stats-grid">
            <div class="stat-card">
              <h3>Total Students</h3>
              <p class="stat-value" id="total-students">0</p>
            </div>
            <div class="stat-card">
              <h3>Total Lecturers</h3>
              <p class="stat-value" id="total-lecturers">0</p>
            </div>
            <div class="stat-card">
              <h3>Monthly Attendance Rate</h3>
              <p class="stat-value" id="monthly-attendance">0%</p>
            </div>
            <div class="stat-card">
              <h3>Annual Attendance Rate</h3>
              <p class="stat-value" id="annual-attendance">0%</p>
            </div>
          </div>
        </div>

        <div class="section" id="students">
          <h2>Manage Students</h2>
          <button id="add-student-btn" class="btn-primary">Add New Student</button>
          <div id="student-form-container" class="form-container hidden">
            <form id="student-form">
              <div class="form-group">
                <label for="student-name">Student Name *</label>
                <input type="text" id="student-name" name="name" required>
                <p class="field-error" id="student-name-error"></p>
              </div>
              <div class="form-group">
                <label for="student-email">Student Email *</label>
                <input type="email" id="student-email" name="email" required>
                <p class="field-error" id="student-email-error"></p>
              </div>
              <div class="form-group">
                <label for="student-department">Department *</label>
                <select id="student-department" name="department" required>
                  <option value="">Select Department</option>
                  <option value="CSN">CSN</option>
                  <option value="SWE">SWE</option>
                  <option value="CGWD">CGWD</option>
                  <option value="NWS">NWS</option>
                  <option value="HR">HR</option>
                </select>
                <p class="field-error" id="student-department-error"></p>
              </div>
              <div class="form-group">
                <label for="student-gender">Gender *</label>
                <select id="student-gender" name="gender" required>
                  <option value="">Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
                <p class="field-error" id="student-gender-error"></p>
              </div>
              <div class="form-group">
                <label for="student-phone">Student Phone Number (9 digits) *</label>
                <input type="tel" id="student-phone" name="phone_number" input type="tel" pattern="[0-9]*" minlength="9" maxlength="9" required>
                <p class="field-error" id="student-phone-error"></p>
              </div>
              <div class="form-group">
                <label for="parent-phone">Parent/Guardian Phone Number (9 digits) *</label>
                <input type="tel" id="parent-phone" name="parent_phone" input type="tel" pattern="[0-9]*" minlength="9" maxlength="9" required>
                <p class="field-error" id="parent-phone-error"></p>
              </div>
              <div class="form-group">
                <label for="guardian-email">Parent/Guardian Email *</label>
                <input type="email" id="guardian-email" name="guardian_email" required>
                <p class="field-error" id="guardian-email-error"></p>
              </div>
              <div class="form-group">
                <label for="student-face">Student Picture (Face Image) *</label>
                <input type="file" id="student-face" name="face_image" accept="image/*" required>
                <p class="field-error" id="student-face-error"></p>
                <div id="preview-container" class="preview-container">
                  <img id="face-preview" src="" alt="Face preview" class="hidden">
                </div>
              </div>
              <div class="form-buttons">
                <button type="submit" class="btn-primary">Add Student</button>
                <button type="button" id="cancel-student-btn" class="btn-secondary">Cancel</button>
              </div>
              <p id="student-form-error" class="error-message"></p>
            </form>
          </div>
          <div id="students-list" class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Department</th>
                  <th>Gender</th>
                  <th>Phone</th>
                  <th>Parent Phone</th>
                  <th>Guardian Email</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="students-tbody"></tbody>
            </table>
          </div>
        </div>

        <!-- EDIT STUDENT MODAL -->
        <div id="edit-student-modal" class="modal-overlay hidden">
          <div class="modal-card">
            <h3>Edit Student</h3>
            <form id="edit-student-form">
              <input type="hidden" id="edit-student-id">
              <div class="form-group">
                <label>Name *</label>
                <input type="text" id="edit-student-name" required>
              </div>
              <div class="form-group">
                <label>Department *</label>
                <select id="edit-student-department" required>
                  <option value="">Select Department</option>
                  <option value="Computer Science">Computer Science</option>
                  <option value="CSN">CSN</option>
                  <option value="SWE">SWE</option>
                  <option value="CGWD">CGWD</option>
                  <option value="NWS">NWS</option>
                  <option value="HR">HR</option>
                  <option value="Engineering">Engineering</option>
                  <option value="Business">Business</option>
                </select>
              </div>
              <div class="form-group">
                <label>Gender *</label>
                <select id="edit-student-gender" required>
                  <option value="">Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="edit-student-phone">
              </div>
              <div class="form-group">
                <label>Parent Phone</label>
                <input type="tel" id="edit-student-parent-phone">
              </div>
              <div class="form-group">
                <label>Guardian Email *</label>
                <input type="email" id="edit-student-guardian-email" required>
              </div>
              <p id="edit-student-error" class="error-message"></p>
              <div class="form-buttons">
                <button type="submit" class="btn-primary">Save Changes</button>
                <button type="button" id="cancel-edit-student-btn" class="btn-secondary">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <div class="section" id="lecturers">
          <h2>Manage Lecturers</h2>
          <button id="add-lecturer-btn" class="btn-primary">Add New Lecturer</button>
          <div id="lecturer-form-container" class="form-container hidden">
            <form id="lecturer-form">
              <div class="form-group">
                <label for="lecturer-name">Lecturer Name *</label>
                <input type="text" id="lecturer-name" name="name" required>
                <p class="field-error" id="lecturer-name-error"></p>
              </div>
              <div class="form-group">
                <label for="lecturer-email">Lecturer Email *</label>
                <input type="email" id="lecturer-email" name="email" required>
                <p class="field-error" id="lecturer-email-error"></p>
              </div>
              <div class="form-group">
                <label for="lecturer-phone">Lecturer Phone Number (9 digits) *</label>
                <input type="tel" id="lecturer-phone" name="phone_number" input type="tel" pattern="[0-9]*" minlength="9" maxlength="9" required>
                <p class="field-error" id="lecturer-phone-error"></p>
              </div>
              <div class="form-group">
                <label for="lecturer-courses">Courses Teaching *</label>
                <select id="lecturer-courses" name="courses_teaching" required>
                  <option value="">Select Course</option>
                  <option value="Maths">Maths</option>
                  <option value="English">English</option>
                  <option value="French">French</option>
                  <option value="Digital Literacy">Digital Literacy</option>
                  <option value="Case Study">Case Study</option>
                </select>
                <p class="field-error" id="lecturer-courses-error"></p>
              </div>
              <div class="form-group">
                <label for="lecturer-gender">Gender *</label>
                <select id="lecturer-gender" name="gender" required>
                  <option value="">Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
                <p class="field-error" id="lecturer-gender-error"></p>
              </div>
              <div class="form-group">
                <label for="lecturer-image">Lecturer Image *</label>
                <input type="file" id="lecturer-image" name="lecturer_image" accept="image/*" required>
                <p class="field-error" id="lecturer-image-error"></p>
                <div id="lecturer-preview-container" class="preview-container">
                  <img id="lecturer-preview" src="" alt="Lecturer preview" class="hidden">
                </div>
              </div>
              <div class="form-buttons">
                <button type="submit" class="btn-primary">Add Lecturer</button>
                <button type="button" id="cancel-lecturer-btn" class="btn-secondary">Cancel</button>
              </div>
              <p id="lecturer-form-error" class="error-message"></p>
            </form>
          </div>
          <div id="lecturers-list" class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Gender</th>
                  <th>Courses</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="lecturers-tbody"></tbody>
            </table>
          </div>
        </div>

        <!-- EDIT LECTURER MODAL -->
        <div id="edit-lecturer-modal" class="modal-overlay hidden">
          <div class="modal-card">
            <h3>Edit Lecturer</h3>
            <form id="edit-lecturer-form">
              <input type="hidden" id="edit-lecturer-id">
              <div class="form-group">
                <label>Name *</label>
                <input type="text" id="edit-lecturer-name" required>
              </div>
              <div class="form-group">
                <label>Gender *</label>
                <select id="edit-lecturer-gender" required>
                  <option value="">Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="edit-lecturer-phone">
              </div>
              <div class="form-group">
                <label>Courses (comma separated)</label>
                <input type="text" id="edit-lecturer-courses" placeholder="e.g. CS101, CS102">
              </div>
              <p id="edit-lecturer-error" class="error-message"></p>
              <div class="form-buttons">
                <button type="submit" class="btn-primary">Save Changes</button>
                <button type="button" id="cancel-edit-lecturer-btn" class="btn-secondary">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <div class="section" id="attendance">
  <h2>Attendance Reports</h2>
  <div class="filter-controls">
    <select id="report-month" class="form-control">
      <option value="">Select Month</option>
      <option value="1">January</option>
      <option value="2">February</option>
      <option value="3">March</option>
      <option value="4">April</option>
      <option value="5">May</option>
      <option value="6">June</option>
      <option value="7">July</option>
      <option value="8">August</option>
      <option value="9">September</option>
      <option value="10">October</option>
      <option value="11">November</option>
      <option value="12">December</option>
    </select>
    <button id="filter-report-btn" class="btn-primary">Filter</button>
  </div>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Date</th>
          <th>Status</th>
          <th>Confidence</th>
        </tr>
      </thead>
      <tbody id="attendance-tbody"></tbody>
    </table>
  </div>
</div>

        <div class="section" id="notifications">
          <h2>Send Notifications</h2>
          <div class="form-group">
            <label for="notification-month">Select Month</label>
            <select id="notification-month" class="form-control">
              <option value="">Select Month</option>
              <option value="1">January</option>
              <option value="2">February</option>
              <option value="3">March</option>
              <option value="4">April</option>
              <option value="5">May</option>
              <option value="6">June</option>
              <option value="7">July</option>
              <option value="8">August</option>
              <option value="9">September</option>
              <option value="10">October</option>
              <option value="11">November</option>
              <option value="12">December</option>
            </select>
            <button id="send-notifications-btn" class="btn-primary">Send to All Guardians</button>
          </div>
          <p id="notification-status" class="info-message"></p>
        </div>

        <!-- ADMIN PROFILE SECTION -->
        <div class="section" id="admin-profile">
          <h2>My Profile</h2>
          <div class="profile-card">
            <div class="profile-details">
              <div class="profile-row"><span class="profile-label">Role</span><span class="profile-value">Administrator</span></div>
              <div class="profile-row"><span class="profile-label">Email</span><span class="profile-value" id="ap-email">—</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LECTURER PAGE -->
    <div id="lecturer-page" class="page">
      <button class="hamburger-btn" onclick="toggleSidebar(this)">
        <span></span><span></span><span></span>
      </button>
      <div class="sidebar-overlay" onclick="closeSidebar(this)"></div>
      <div class="sidebar">
        <div class="sidebar-header">
          <h2>Lecturer Dashboard</h2>
        </div>
        <nav class="sidebar-nav">
          <a href="#" data-section="attendance-home" class="nav-link active">Home</a>
          <a href="#" data-section="take-attendance" class="nav-link">Take Attendance</a>
          <a href="#" data-section="my-attendance" class="nav-link">My Attendance Records</a>
          <button type="button" id="logout-btn" class="nav-link">Logout</button>
        </nav>
      </div>
      <div class="main-content">
        <div class="section active" id="attendance-home">
          <h2>Welcome, Lecturer</h2>
          <div class="stats-grid">
            <div class="stat-card">
              <h3>Classes Today</h3>
              <p class="stat-value" id="classes-today">0</p>
            </div>
            <div class="stat-card">
              <h3>Attendance Recorded</h3>
              <p class="stat-value" id="attendance-recorded">0</p>
            </div>
          </div>
        </div>

        <div class="section" id="take-attendance">
          <h2>Take Attendance</h2>

          <!-- Mode Selector -->
          <div id="attendance-mode-selector" class="attendance-mode-selector">
            <p class="mode-label">Choose attendance method:</p>
            <div class="mode-buttons">
              <button id="mode-live-btn" class="mode-btn active" data-mode="live">
                <span class="mode-icon">📷</span>
                <span class="mode-title">Live Camera</span>
                <span class="mode-desc">Use webcam to scan faces in real-time</span>
              </button>
              <button id="mode-upload-btn" class="mode-btn" data-mode="upload">
                <span class="mode-icon">🖼️</span>
                <span class="mode-title">Upload Image</span>
                <span class="mode-desc">Upload a class photo to mark attendance</span>
              </button>
            </div>
          </div>

          <!-- Session Controls -->
          <div id="session-controls" class="session-controls">
            <button id="start-attendance-btn" class="btn-primary btn-large">▶ Start Session</button>
          </div>

          <!-- LIVE CAMERA MODE -->
          <div id="camera-container" class="camera-container hidden">
            <div class="video-wrapper">
              <video id="attendance-video" autoplay playsinline></video>
              <canvas id="attendance-canvas" class="hidden"></canvas>
              <div id="scan-overlay" class="scan-overlay hidden">
                <div class="scan-line"></div>
                <p class="scan-text">Scanning…</p>
              </div>
            </div>
            <div class="attendance-controls">
              <button id="end-attendance-btn" class="btn-secondary">⏹ End Session</button>
            </div>
            <div id="detection-result" class="detection-result">
              <p id="detection-message" class="detection-message"></p>
              <div id="matched-students" class="matched-students"></div>
            </div>
          </div>

          <!-- UPLOAD IMAGE MODE -->
          <div id="upload-container" class="upload-container hidden">
            <div class="upload-area" id="upload-drop-area" role="button" tabindex="0" title="Click to choose a photo">
              <span class="upload-icon">🖼️</span>
              <p class="upload-heading">Drag &amp; drop a class photo here</p>
              <p class="upload-subtext">— or —</p>
              <label for="attendance-image-input" class="btn-primary upload-label" onclick="event.stopPropagation()">📁 Choose Image</label>
              <input type="file" id="attendance-image-input" accept="image/*" style="display:none">
            </div>
            <img id="upload-preview" class="upload-preview hidden" alt="Preview">
            <div class="attendance-controls" id="upload-controls" style="display:none">
              <button id="process-image-btn" class="btn-primary">🔍 Process Image</button>
              <button id="end-upload-btn" class="btn-secondary">⏹ End Session</button>
            </div>
            <div id="upload-detection-result" class="detection-result">
              <p id="upload-detection-message" class="detection-message"></p>
            </div>
          </div>

          <!-- Auto-Attendance Log (shared by both modes) -->
          <div id="auto-attendance-log" class="auto-attendance-log hidden">
            <h3>Session Attendance Log</h3>
            <div id="log-entries" class="log-entries"></div>
            <div class="log-summary">
              <span id="log-present-count" class="log-present">✅ Present: 0</span>
              <span id="log-absent-count" class="log-absent">❌ Absent: 0</span>
            </div>
          </div>
        </div>

        <div class="section" id="my-attendance">
          <h2>Attendance Records</h2>
          <div id="lecturer-attendance-list" class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="lecturer-attendance-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- STUDENT PAGE -->
    <div id="student-page" class="page">
      <button class="hamburger-btn" onclick="toggleSidebar(this)">
        <span></span><span></span><span></span>
      </button>
      <div class="sidebar-overlay" onclick="closeSidebar(this)"></div>
      <div class="sidebar">
        <div class="sidebar-header">
          <h2>Student Dashboard</h2>
        </div>
        <nav class="sidebar-nav">
          <a href="#" data-section="student-home" class="nav-link active">Home</a>
          <a href="#" data-section="view-attendance" class="nav-link">My Attendance</a>
          <a href="#" data-section="student-profile" class="nav-link">My Profile</a>
          <button type="button" id="logout-btn" class="nav-link">Logout</button>
        </nav>
      </div>
      <div class="main-content">
        <div class="section active" id="student-home">
          <h2>Welcome</h2>
          <div class="stats-grid">
            <div class="stat-card">
              <h3>Monthly Attendance</h3>
              <p class="stat-value" id="student-monthly">0%</p>
            </div>
            <div class="stat-card">
              <h3>Classes Attended</h3>
              <p class="stat-value" id="classes-attended">0</p>
            </div>
            <div class="stat-card">
              <h3>Classes Missed</h3>
              <p class="stat-value" id="classes-missed">0</p>
            </div>
          </div>
        </div>

        <div class="section" id="view-attendance">
          <h2>Monthly Attendance Sheet</h2>
          <div class="filter-controls">
            <select id="student-month" class="form-control">
              <option value="">Select Month</option>
              <option value="1">January</option>
              <option value="2">February</option>
              <option value="3">March</option>
              <option value="4">April</option>
              <option value="5">May</option>
              <option value="6">June</option>
              <option value="7">July</option>
              <option value="8">August</option>
              <option value="9">September</option>
              <option value="10">October</option>
              <option value="11">November</option>
              <option value="12">December</option>
            </select>
            <button id="view-month-btn" class="btn-primary">View</button>
          </div>
          <div id="student-attendance-list" class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Course</th>
                </tr>
              </thead>
              <tbody id="student-attendance-tbody"></tbody>
            </table>
          </div>
        </div>

        <!-- STUDENT PROFILE SECTION -->
        <div class="section" id="student-profile">
          <h2>My Profile</h2>
          <div class="profile-card" id="student-profile-card">
            <div class="profile-image-wrap">
              <img id="student-profile-img" src="" alt="Profile Photo" class="profile-img hidden">
              <div id="student-no-img" class="profile-no-img">No Photo</div>
            </div>
            <div class="profile-details">
              <div class="profile-row"><span class="profile-label">Full Name</span><span class="profile-value" id="sp-name">—</span></div>
              <div class="profile-row"><span class="profile-label">Email</span><span class="profile-value" id="sp-email">—</span></div>
              <div class="profile-row"><span class="profile-label">Department</span><span class="profile-value" id="sp-department">—</span></div>
              <div class="profile-row"><span class="profile-label">Phone Number</span><span class="profile-value" id="sp-phone">—</span></div>
              <div class="profile-row"><span class="profile-label">Parent Phone</span><span class="profile-value" id="sp-parent-phone">—</span></div>
              <div class="profile-row"><span class="profile-label">Guardian Email</span><span class="profile-value" id="sp-guardian-email">—</span></div>
              <div class="profile-row"><span class="profile-label">Registered On</span><span class="profile-value" id="sp-created">—</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mock data layer: intercepts API calls with realistic pre-filled data -->
  <script type="module" src="js/mock-data.js"></script>
  <script type="module" src="js/main.js"></script>
</body>
</html>
