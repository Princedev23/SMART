// ─── helpers ──────────────────────────────────────────────────────────────────
function daysAgo(n) {
  const d = new Date();
  d.setDate(d.getDate() - n);
  return d.toISOString().split('T')[0];
}

const MOCK_USERS = {
  'student@school.edu':  { id:10, email:'student@school.edu',  role:'student',  password:'demo123' },
  'lecturer@school.edu': { id:20, email:'lecturer@school.edu', role:'lecturer', password:'demo123' },
  'admin@school.edu':    { id:30, email:'admin@school.edu',    role:'admin',    password:'demo123' },
};

// gender field added; phone values are real strings (not empty) so '-' renders correctly
const MOCK_STUDENTS = [
  { id:1,  user_id:10, name:'Alice Mbeki',   email:'alice@school.edu',  department:'CSN',  gender:'Female', phone_number:'677001001', parent_phone:'677001002', guardian_email:'alice.parent@gmail.com',  face_image_path:null, created_at:'2025-09-01 08:00:00' },
  { id:2,  user_id:11, name:'Bruno Nkemba',  email:'bruno@school.edu',  department:'SWE',  gender:'Male',   phone_number:'677002001', parent_phone:'677002002', guardian_email:'bruno.parent@gmail.com',  face_image_path:null, created_at:'2025-09-01 08:10:00' },
  { id:3,  user_id:12, name:'Clara Fofung',  email:'clara@school.edu',  department:'CGWD', gender:'Female', phone_number:'677003001', parent_phone:'677003002', guardian_email:'clara.parent@gmail.com',  face_image_path:null, created_at:'2025-09-01 08:20:00' },
  { id:4,  user_id:13, name:'David Etonde',  email:'david@school.edu',  department:'NWS',  gender:'Male',   phone_number:'677004001', parent_phone:'677004002', guardian_email:'david.parent@gmail.com',  face_image_path:null, created_at:'2025-09-01 08:30:00' },
  { id:5,  user_id:14, name:'Esther Njang',  email:'esther@school.edu', department:'HR',   gender:'Female', phone_number:'677005001', parent_phone:'677005002', guardian_email:'esther.parent@gmail.com', face_image_path:null, created_at:'2025-09-02 09:00:00' },
  { id:6,  user_id:15, name:'Felix Ayuk',    email:'felix@school.edu',  department:'CSN',  gender:'Male',   phone_number:'677006001', parent_phone:'677006002', guardian_email:'felix.parent@gmail.com',  face_image_path:null, created_at:'2025-09-02 09:10:00' },
  { id:7,  user_id:16, name:'Grace Bih',     email:'grace@school.edu',  department:'SWE',  gender:'Female', phone_number:'677007001', parent_phone:'677007002', guardian_email:'grace.parent@gmail.com',  face_image_path:null, created_at:'2025-09-03 10:00:00' },
  { id:8,  user_id:17, name:'Henry Tabi',    email:'henry@school.edu',  department:'CGWD', gender:'Male',   phone_number:'677008001', parent_phone:'677008002', guardian_email:'henry.parent@gmail.com',  face_image_path:null, created_at:'2025-09-03 10:15:00' },
  { id:9,  user_id:18, name:'victory',       email:'victory@gmail.com', department:'CSN',  gender:'Male',   phone_number:'675432136', parent_phone:'677895579', guardian_email:'parent@gmail.com',        face_image_path:null, created_at:'2025-09-04 11:00:00' },
];

const MY_STUDENT = MOCK_STUDENTS[0];

const MOCK_LECTURERS = [
  { id:1, user_id:20, name:'Prof. Jean Kamga',    email:'lecturer@school.edu', gender:'Male',   phone_number:'699100001', courses_teaching:['Maths','Digital Literacy'], lecturer_image_path:null, created_at:'2025-08-15 07:00:00' },
  { id:2, user_id:21, name:'Dr. Sophie Ndongo',   email:'sophie@school.edu',   gender:'Female', phone_number:'699100002', courses_teaching:['English','French'],          lecturer_image_path:null, created_at:'2025-08-15 07:30:00' },
  { id:3, user_id:22, name:'Mr. Patrick Etoundi', email:'patrick@school.edu',  gender:'Male',   phone_number:'699100003', courses_teaching:['Case Study'],                lecturer_image_path:null, created_at:'2025-08-16 08:00:00' },
];

const MY_LECTURER = MOCK_LECTURERS[0];

function buildStudentAttendance() {
  const records = [];
  const pattern = ['present','present','present','present','absent'];
  const cs = [['Maths','Digital Literacy'],['English','French'],['Case Study']];
  for (let i = 0; i < 30; i++) {
    const date = daysAgo(i);
    if ([0,6].includes(new Date(date).getDay())) continue;
    const status = pattern[i % pattern.length];
    records.push({ id:1000+i, student_id:MY_STUDENT.id, lecturer_id:MY_LECTURER.id, date, time:'08:00:00', status, confidence_score: status==='present'?+(0.88+Math.random()*0.11).toFixed(3):0, courses_teaching:cs[i%cs.length], student_name:MY_STUDENT.name });
  }
  return records;
}

function buildAllAttendance() {
  const records = []; let id = 2000;
  MOCK_STUDENTS.forEach((student, si) => {
    for (let i = 0; i < 25; i++) {
      const date = daysAgo(i);
      if ([0,6].includes(new Date(date).getDay())) continue;
      const lec = MOCK_LECTURERS[(si+i)%MOCK_LECTURERS.length];
      const absent = (si===2&&i<5)||(si===5&&i%4===0);
      records.push({ id:id++, student_id:student.id, lecturer_id:lec.id, date, time:'08:00:00', status:absent?'absent':'present', confidence_score:absent?0:+(0.87+Math.random()*0.12).toFixed(3), student_name:student.name, courses_teaching:lec.courses_teaching });
    }
  });
  return records;
}

const STUDENT_ATTENDANCE = buildStudentAttendance();
const ALL_ATTENDANCE     = buildAllAttendance();
let _lecturerRecords     = ALL_ATTENDANCE.filter(r => r.lecturer_id === MY_LECTURER.id);

export const mockHandlers = {
  login(email,password,role){ const u=MOCK_USERS[email.toLowerCase()]; if(!u||u.password!==password||u.role!==role) return {success:false,error:'Invalid credentials. Use password: demo123'}; return {success:true,user:{id:u.id,email:u.email,role:u.role}}; },
  logout(){ return {success:true}; },
  checkAuth(){ return {success:false,error:'Not authenticated'}; },

  getStudents(){ return {success:true,data:MOCK_STUDENTS}; },
  addStudent(formData){
    const name=formData.get('name')||'New Student';
    const email=formData.get('email')||`student${Date.now()}@school.edu`;
    const newS={ id:900+MOCK_STUDENTS.length, user_id:900+MOCK_STUDENTS.length, name, email,
      department:formData.get('department')||'CSN',
      gender:formData.get('gender')||'Male',
      phone_number:formData.get('phone_number')||null,
      parent_phone:formData.get('parent_phone')||null,
      guardian_email:formData.get('guardian_email')||'',
      face_image_path:null, created_at:new Date().toISOString() };
    MOCK_STUDENTS.push(newS);
    return {success:true,credentials:{email,password:'demo123'}};
  },
  deleteStudent(id){ const i=MOCK_STUDENTS.findIndex(s=>s.id===Number(id)); if(i>-1)MOCK_STUDENTS.splice(i,1); return {success:true}; },
  updateStudent(data){ const s=MOCK_STUDENTS.find(s=>s.id===Number(data.id)); if(s)Object.assign(s,data); return {success:true}; },
  getStudentProfile(){ return {success:true,data:{...MY_STUDENT}}; },

  getLecturers(){ return {success:true,data:MOCK_LECTURERS}; },
  addLecturer(formData){
    const name=formData.get('name')||'New Lecturer';
    const email=formData.get('email')||`lec${Date.now()}@school.edu`;
    const newL={ id:800+MOCK_LECTURERS.length, user_id:800+MOCK_LECTURERS.length, name, email,
      gender:formData.get('gender')||'Male',
      phone_number:formData.get('phone_number')||null,
      courses_teaching:[formData.get('courses_teaching')||'Maths'],
      lecturer_image_path:null, created_at:new Date().toISOString() };
    MOCK_LECTURERS.push(newL);
    return {success:true,credentials:{email,password:'demo123'}};
  },
  deleteLecturer(id){ const i=MOCK_LECTURERS.findIndex(l=>l.id===Number(id)); if(i>-1)MOCK_LECTURERS.splice(i,1); return {success:true}; },
  updateLecturer(data){ const l=MOCK_LECTURERS.find(l=>l.id===Number(data.id)); if(l)Object.assign(l,data); return {success:true}; },
  getLecturerProfile(){ return {success:true,data:{...MY_LECTURER}}; },

  getStudentAttendance(month,year){ const m=Number(month),y=Number(year); return {success:true,data:STUDENT_ATTENDANCE.filter(r=>{const d=new Date(r.date);return d.getMonth()+1===m&&d.getFullYear()===y;})}; },
  getLecturerAttendance(){ return {success:true,data:_lecturerRecords}; },
  recordAttendance(data){
    const already=_lecturerRecords.find(r=>r.student_id===Number(data.student_id)&&r.date===data.date);
    if(!already){ const st=MOCK_STUDENTS.find(s=>s.id===Number(data.student_id)); _lecturerRecords.unshift({id:9000+_lecturerRecords.length,student_id:Number(data.student_id),lecturer_id:MY_LECTURER.id,date:data.date,time:new Date().toTimeString().split(' ')[0],status:data.status,confidence_score:data.confidence_score||0.95,student_name:st?st.name:'Unknown',courses_teaching:MY_LECTURER.courses_teaching}); }
    return {success:true};
  },
  updateAttendance(data){ const r=_lecturerRecords.find(r=>r.id===Number(data.id)); if(r)r.status=data.status; return {success:true}; },
  deleteAttendance(id){ const i=_lecturerRecords.findIndex(r=>r.id===Number(id)); if(i>-1)_lecturerRecords.splice(i,1); return {success:true}; },
  getAttendanceByDate(date){ return {success:true,data:ALL_ATTENDANCE.filter(r=>r.date===date)}; },
  getAttendanceByStudent(sid,month,year){ const m=Number(month),y=Number(year); return {success:true,data:ALL_ATTENDANCE.filter(r=>{const d=new Date(r.date);return r.student_id===Number(sid)&&d.getMonth()+1===m&&d.getFullYear()===y;})}; },
  getMonthlyStats(month,year){ const m=Number(month),y=Number(year); return {success:true,data:ALL_ATTENDANCE.filter(r=>{const d=new Date(r.date);return d.getMonth()+1===m&&d.getFullYear()===y;})}; },
  getAdminStats(){ return {success:true,data:{total_students:MOCK_STUDENTS.length,total_lecturers:MOCK_LECTURERS.length}}; },
  getMonthlyAttendanceRate(month,year){ const m=Number(month),y=Number(year); const recs=ALL_ATTENDANCE.filter(r=>{const d=new Date(r.date);return d.getMonth()+1===m&&d.getFullYear()===y;}); const p=recs.filter(r=>r.status==='present').length; return {success:true,data:{rate:recs.length?Math.round(p/recs.length*100):0}}; },
  getAnnualAttendanceRate(year){ const y=Number(year); const recs=ALL_ATTENDANCE.filter(r=>new Date(r.date).getFullYear()===y); const p=recs.filter(r=>r.status==='present').length; return {success:true,data:{rate:recs.length?Math.round(p/recs.length*100):0}}; },
  sendNotifications(month,year){ return {success:true,message:`Notifications sent to ${MOCK_STUDENTS.length} guardians for ${month}/${year}.`}; },

  // used by face-recognition bypass (see below)
  getAllStudents(){ return MOCK_STUDENTS; },
};

// ─── fetch interceptor ────────────────────────────────────────────────────────
const _realFetch = window.fetch.bind(window);
window.fetch = async function(url, options={}) {
  const urlStr = typeof url==='string'?url:url.toString();
  if (!urlStr.includes('/api/') && !urlStr.includes('api/')) return _realFetch(url,options);
  const urlObj=new URL(urlStr,location.origin), action=urlObj.searchParams.get('action')||'', body=options.body;
  let result;
  try {
    if (urlStr.includes('auth.php')) {
      if (action==='login'){const d=JSON.parse(body||'{}');result=mockHandlers.login(d.email,d.password,d.role);}
      else if(action==='logout'){result=mockHandlers.logout();}
      else if(action==='check'){result=mockHandlers.checkAuth();}
      else result={success:false,error:'Unknown auth action'};
    } else if (urlStr.includes('student.php')) {
      if(action==='list')result=mockHandlers.getStudents();
      else if(action==='add')result=mockHandlers.addStudent(body instanceof FormData?body:new FormData());
      else if(action==='delete')result=mockHandlers.deleteStudent(urlObj.searchParams.get('id'));
      else if(action==='profile')result=mockHandlers.getStudentProfile();
      else if(action==='update')result=mockHandlers.updateStudent(JSON.parse(body||'{}'));
      else result={success:false,error:'Unknown student action'};
    } else if (urlStr.includes('lecturere.php')||urlStr.includes('lecturer.php')) {
      if(action==='list')result=mockHandlers.getLecturers();
      else if(action==='add')result=mockHandlers.addLecturer(body instanceof FormData?body:new FormData());
      else if(action==='delete')result=mockHandlers.deleteLecturer(urlObj.searchParams.get('id'));
      else if(action==='profile')result=mockHandlers.getLecturerProfile();
      else if(action==='update')result=mockHandlers.updateLecturer(JSON.parse(body||'{}'));
      else result={success:false,error:'Unknown lecturer action'};
    } else if (urlStr.includes('attendance.php')) {
      if(action==='record')result=mockHandlers.recordAttendance(JSON.parse(body||'{}'));
      else if(action==='update')result=mockHandlers.updateAttendance(JSON.parse(body||'{}'));
      else if(action==='delete')result=mockHandlers.deleteAttendance(urlObj.searchParams.get('id'));
      else if(action==='get_by_date')result=mockHandlers.getAttendanceByDate(urlObj.searchParams.get('date'));
      else if(action==='get_by_student')result=mockHandlers.getAttendanceByStudent(urlObj.searchParams.get('student_id'),urlObj.searchParams.get('month'),urlObj.searchParams.get('year'));
      else if(action==='get_student_attendance')result=mockHandlers.getStudentAttendance(urlObj.searchParams.get('month'),urlObj.searchParams.get('year'));
      else if(action==='get_lecturer_attendance')result=mockHandlers.getLecturerAttendance();
      else if(action==='get_monthly_stats')result=mockHandlers.getMonthlyStats(urlObj.searchParams.get('month'),urlObj.searchParams.get('year'));
      else result={success:false,error:'Unknown attendance action'};
    } else if (urlStr.includes('admin.php')) {
      if(action==='get_stats')result=mockHandlers.getAdminStats();
      else if(action==='get_monthly_attendance_rate')result=mockHandlers.getMonthlyAttendanceRate(urlObj.searchParams.get('month'),urlObj.searchParams.get('year'));
      else if(action==='get_annual_attendance_rate')result=mockHandlers.getAnnualAttendanceRate(urlObj.searchParams.get('year'));
      else if(action==='send_notifications'){const d=JSON.parse(body||'{}');result=mockHandlers.sendNotifications(d.month,d.year);}
      else result={success:false,error:'Unknown admin action'};
    } else {
      result={success:false,error:'Unknown endpoint'};
    }
  } catch(err){ console.error('[mock-data]',err); result={success:false,error:String(err)}; }
  return new Response(JSON.stringify(result),{status:200,headers:{'Content-Type':'application/json'}});
};

// ─── FACE RECOGNITION BYPASS ──────────────────────────────────────────────────
// Mock students have no real face_descriptor so detectFace() always returns [].
// We patch lecturer.js's detectFace import at the module level by replacing the
// global getStudents response to embed fake descriptors, AND we patch the
// face-api on the window so any detected face matches all students.
// Strategy: override detectFace on the window so lecturer.js picks it up.
window.__mockDetectFace = async function(canvas, students) {
  // Return all students as matches so the lecturer sees everyone
  return students.map((student, i) => ({
    student,
    confidence: Math.max(0.60, 0.97 - i * 0.03),
    distance: 0.03 + i * 0.03,
  }));
};

console.log('[SMART Demo] Mock layer active | Credentials: student@school.edu / lecturer@school.edu / admin@school.edu | password: demo123');
