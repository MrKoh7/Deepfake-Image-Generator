<template>
  <div class="training-container">
    <h2>Deepfake Generator Training</h2>
    <p>Upload a dataset of images to start training your model.</p>

    <!-- Upload Box -->
    <div class="drop-box" @dragover.prevent @drop.prevent="handleDrop">
      <input
        type="file"
        id="fileInput"
        multiple
        accept="image/*"
        @change="handleFileSelect"
        hidden
      />
      <label for="fileInput" class="upload-label">
        Drag & Drop images here or click to select
      </label>
    </div>

    <!-- Preview -->
    <div v-if="files.length" class="preview">
      <h3>Selected Files:</h3>
      <ul>
        <li v-for="(file, index) in files" :key="index">
          {{ file.name }}
        </li>
      </ul>
    </div>

    <!-- Upload + Train Button -->
    <button :disabled="!files.length || loading" @click="uploadDataset">
      <span v-if="loading" class="spinner"></span>
      <span v-else>Upload & Train</span>
    </button>

    <!-- Status + Progress -->
    <div v-if="statusMessage" class="status">
      {{ statusMessage }}
    </div>
    <div v-if="loading" class="progress-bar">
      <div class="progress-fill"></div>
    </div>

    <!-- Download ZIP -->
    <div v-if="downloadUrl" class="download-link">
      <a :href="downloadUrl" target="_blank" class="btn-download">
        ⬇ Download Trained Model (ZIP)
      </a>
    </div>

    <!-- Test Generator -->
    <div v-if="downloadUrl" class="test-generator">
      <button @click="testGenerator" class="btn-download">
        🧪 Test Generator
      </button>
      <div v-if="testImage" class="test-preview">
        <h3>Sample Output:</h3>
        <img :src="testImage" alt="Generated sample" class="preview-img" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

// ✅ ensure axios always sends cookies
axios.defaults.withCredentials = true;

const files = ref([]);
const loading = ref(false);
const statusMessage = ref('');
const downloadUrl = ref(null);
const testImage = ref(null);
const modelPath = ref(null);

// Handle file select & drop
function handleFileSelect(event) {
  files.value = Array.from(event.target.files);
}
function handleDrop(event) {
  files.value = Array.from(event.dataTransfer.files);
}

// Upload dataset → Train model
async function uploadDataset() {
  if (!files.value.length) return;

  loading.value = true;
  statusMessage.value = 'Uploading dataset...';

  try {
    // 1. Upload dataset
    const formData = new FormData();
    files.value.forEach((file) => formData.append('files[]', file));

    const uploadRes = await axios.post(
      'http://127.0.0.1:8000/api/upload-dataset',
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      }
    );

    if (uploadRes.data.status !== 'success') {
      throw new Error(uploadRes.data.message || 'Upload failed');
    }

    const datasetPath = uploadRes.data.path;
    statusMessage.value = 'Dataset uploaded! Starting training...';

    // 2. Call training API
    const trainRes = await axios.post(
      'http://127.0.0.1:8000/api/train-generator',
      { dataset_path: datasetPath }
    );

    if (trainRes.data.status === 'success') {
      statusMessage.value = '✅ Training completed! Model saved.';
      downloadUrl.value = trainRes.data.zip_download;
      modelPath.value = trainRes.data.model_path;
    } else {
      throw new Error(trainRes.data.message || 'Training failed');
    }
  } catch (err) {
    statusMessage.value =
      '❌ Error: ' + (err.response?.data?.message || err.message);
  } finally {
    loading.value = false;
  }
}

// Test trained generator
async function testGenerator() {
  if (!modelPath.value) {
    statusMessage.value = '❌ No trained model available to test.';
    return;
  }

  statusMessage.value = '🔄 Testing generator...';
  try {
    const res = await axios.post('http://127.0.0.1:8000/api/test-generator', {
      model_path: modelPath.value,
    });
    if (res.data.status === 'success') {
      statusMessage.value = '✅ Test completed!';
      testImage.value = res.data.image + '?t=' + new Date().getTime();
    } else {
      throw new Error(res.data.message);
    }
  } catch (err) {
    statusMessage.value =
      '❌ Error: ' + (err.response?.data?.message || err.message);
  }
}
</script>


<style scoped>
/* =====================
   Training Container
===================== */
.training-container {
  background: #2a3247;
  padding: 40px;
  border-radius: 14px;
  color: white;
  text-align: center;
  max-width: 800px;        /* make container bigger */
  margin: 60px auto;       /* center with spacing from top */
  position: relative;
  overflow: hidden;        /* keep glow inside */
  box-sizing: border-box;
}

/* ✅ Glowing Border */
.training-container::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 12px;
  padding: 2px;
  background: linear-gradient(270deg, #00f0ff, #0077ff, #ff00ff, #00f0ff);
  background-size: 600% 600%;
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  animation: borderGlow 6s linear infinite;
  pointer-events: none;
}

@keyframes borderGlow {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* =====================
   Title & Subtitle
===================== */
h2 {
  font-size: 2.2rem;
  color: #00e5ff;
  margin-bottom: 15px;
  text-shadow: 0 0 10px rgba(0, 229, 255, 0.8);
}
p {
  font-size: 1.1rem;
  color: #ccc;
  margin-bottom: 20px;
}

/* =====================
   Drag & Drop
===================== */
.drop-box {
  border: 2px dashed #00e5ff;
  padding: 50px;
  margin: 20px 0;
  border-radius: 12px;
  background: rgba(0, 229, 255, 0.05);
  color: #00e5ff;
  cursor: pointer;
  transition: 0.3s;
}
.drop-box:hover {
  background: rgba(0, 229, 255, 0.12);
  box-shadow: 0 0 12px #00e5ff;
}

/* =====================
   File Preview
===================== */
.preview {
  margin-top: 20px;
  text-align: left;
  max-height: 200px;        /* prevent overflow */
  overflow-y: auto;         /* scroll if too many files */
  padding-right: 8px;
}

.preview h3 {
  color: #00e5ff;
  font-size: 1.2rem;
  margin-bottom: 10px;
}
.preview ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.preview li {
  background: #1f2937;
  margin-bottom: 8px;
  padding: 10px 14px;
  border-radius: 6px;
  font-size: 1rem;
  border: 1px solid #00e5ff33;
  box-shadow: 0 0 5px rgba(0, 229, 255, 0.3);
  word-break: break-word;  /* handle long filenames */
}

/* =====================
   Buttons
===================== */
button,
.btn-download {
  display: inline-block;
  padding: 14px 30px;
  margin: 20px auto;         /* space between buttons */
  font-size: 1.1rem;
  font-weight: bold;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  background: linear-gradient(90deg, #00f0ff, #0077ff);
  color: #fff;
  box-shadow: 0 0 8px rgba(0, 240, 255, 0.6);
  transition: 0.3s;
}
button:hover,
.btn-download:hover {
  box-shadow: 0 0 15px #00f0ff, 0 0 30px #0077ff;
  transform: translateY(-2px);
}
button:disabled {
  background: #444;
  color: #aaa;
  cursor: not-allowed;
  box-shadow: none;
}

/* =====================
   Status Message
===================== */
.status {
  margin-top: 15px;
  margin-bottom: 15px; /* ✅ give space so it won’t overlap */
  font-size: 1rem;
  font-weight: bold;
  color: #00e5ff;
  text-align: center;
}

/* =====================
   Test Generator
===================== */
.test-generator {
  margin-top: 30px;
  text-align: center;
}
.test-preview {
  margin-top: 20px;
}
.preview-img {
  max-width: 250px;         /* ✅ make preview bigger */
  width: 100%;
  border: 2px solid #00e5ff;
  border-radius: 8px;
  margin-top: 10px;
  box-shadow: 0 0 12px rgba(0, 229, 255, 0.6);
}

/* =====================
   Spinner
===================== */
.spinner {
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top: 3px solid #fff;
  border-radius: 50%;

  width: 18px;
  height: 18px;

  display: inline-block;
  vertical-align: middle;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
/* =====================
   Responsive Fixes
===================== */
@media (max-width: 768px) {
  .training-container {
    padding: 20px;
    max-width: 95%;
  }
  h2 {
    font-size: 1.8rem;
  }
  .drop-box {
    padding: 30px;
  }
  .preview-img {
    max-width: 280px; /* smaller preview on mobile */
  }
}
</style>



