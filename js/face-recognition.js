let faceapi = null;
let modelsLoaded = false;

export async function initFaceRecognition() {
  if (modelsLoaded) return;

  if (!window.faceapi) {
    await loadScript('https://cdn.jsdelivr.net/npm/@tensorflow/tfjs');
    await loadScript('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js');
  }

  faceapi = window.faceapi;
  const modelUrl = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';

  await faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl);
  await faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl);
  await faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl);

  modelsLoaded = true;
  console.log('Face recognition models loaded');
}

function loadScript(src) {
  return new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = src;
    s.onload = resolve;
    s.onerror = reject;
    document.head.appendChild(s);
  });
}


export async function captureFaceDescriptorFromCanvas(canvas) {
  if (!modelsLoaded) await initFaceRecognition();

  const detection = await faceapi
    .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions())
    .withFaceLandmarks()
    .withFaceDescriptor();

  if (!detection) return null;
  return detection.descriptor; 
}

/**
 * Detect the live face from a camera canvas and match against stored student descriptors.
 * Used at ATTENDANCE TIME.
 *
 * @param {HTMLCanvasElement} canvas 
 * @param {Array}             students 
 * @param {number}            threshold 
 
 * @returns {Array} matched students sorted best-first: [{ student, confidence, distance }]
 */
export async function detectFace(canvas, students, threshold = 0.5) {
  // ── DEMO MODE BYPASS ──────────────────────────────────────────────────────
  // If none of the students have a real face_descriptor (mock data), skip the
  // real face-api entirely and return all students as matches so the lecturer
  // can see and mark anyone present during the demo.
  const hasRealDescriptors = students.some(s => s.face_descriptor);
  if (!hasRealDescriptors) {
    console.log('[face-recognition] Demo mode: no descriptors stored, returning all students as matches.');
    return students.map((student, i) => ({
      student,
      confidence: Math.max(0.60, 0.97 - i * 0.03),
      distance: 0.03 + i * 0.03,
    }));
  }
  // ─────────────────────────────────────────────────────────────────────────

  if (!modelsLoaded) await initFaceRecognition();

  // Detect face in live camera frame
  const detection = await faceapi
    .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions())
    .withFaceLandmarks()
    .withFaceDescriptor();

  if (!detection) {
    console.log('No face detected in camera frame');
    return [];
  }

  const liveDescriptor = detection.descriptor;
  const matches = [];

  for (const student of students) {
    // Skip students who were registered without face data
    if (!student.face_descriptor) continue;

    let storedDescriptor;
    try {
      storedDescriptor = new Float32Array(JSON.parse(student.face_descriptor));
    } catch (e) {
      console.warn('Bad descriptor for student:', student.name);
      continue;
    }

    // Euclidean distance: 0 = identical, >0.6 = different person (face-api standard)
    const distance = euclideanDistance(liveDescriptor, storedDescriptor);

    if (distance <= threshold) {
      // Convert distance to a 0-100% confidence for display
      const confidence = 1 - Math.min(distance / threshold, 1);
      matches.push({ student, confidence, distance });
    }
  }

  // Best match first (lowest distance)
  return matches.sort((a, b) => a.distance - b.distance);
}

/**
 * Euclidean distance between two Float32Arrays of equal length.
 */
function euclideanDistance(a, b) {
  let sum = 0;
  for (let i = 0; i < a.length; i++) {
    const diff = a[i] - b[i];
    sum += diff * diff;
  }
  return Math.sqrt(sum);
}
