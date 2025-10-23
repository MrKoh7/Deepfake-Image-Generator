<template>
  <div class="generator-box">
    <h1>Deepfake Image Generator</h1>

    <!-- Model Selection -->
    <div class="dropdown">
      <span class="dropdown-label">Select Model:</span>
      <div class="dropdown-selected" @click="toggleDropdown('model')">
        {{ modelLabels[selectedModel] || '-- Choose a model --' }}
        <span class="arrow" :class="{ open: openDropdown === 'model' }"></span>
      </div>
      <ul v-if="openDropdown === 'model'" class="dropdown-menu">
        <li
          v-for="option in modelOptions"
          :key="option.value"
          @click.stop="selectOption('model', option.value)"
        >
          {{ option.label }}
        </li>
      </ul>
    </div>

    <!-- Number of Images -->
    <div class="dropdown">
      <span class="dropdown-label">Number of Images:</span>
      <div class="dropdown-selected" @click="toggleDropdown('count')">
        {{ imageCount }}
        <span class="arrow" :class="{ open: openDropdown === 'count' }"></span>
      </div>
      <ul v-if="openDropdown === 'count'" class="dropdown-menu scrollable">
        <li v-for="n in 20" :key="n" @click.stop="selectOption('count', n)">
          {{ n }}
        </li>
      </ul>
    </div>

    <!-- Generate Button with Spinner -->
    <button :disabled="!selectedModel || loading" @click="generateImage">
      <span v-if="loading" class="spinner"></span>
      <span v-else>Generate</span>
    </button>

    <!-- Show Error -->
    <p v-if="error" class="error">{{ error }}</p>

    <!-- Show Generated Images -->
    <div v-if="imageUrls.length" class="result">
      <h3>Generated Images</h3>
      <div class="grid">
        <div v-for="(url, index) in imageUrls" :key="index" class="image-card">
          <img :src="url" alt="Generated deepfake" />
          <a :href="url" target="_blank" class="download-btn">Preview</a>
        </div>
      </div>

      <!-- Download All Button -->
      <div class="download-all">
        <button @click="downloadAll" class="btn-all">Download as ZIP</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { downloadZip } from '../api';

const selectedModel = ref('');
const imageCount = ref(1);
const imageUrls = ref([]);
const imageNames = ref([]);
const loading = ref(false);
const error = ref(null);

// dropdown state
const openDropdown = ref(null);

const modelOptions = [
  { value: 'asian_male', label: 'Asian (Male)' },
  { value: 'asian_female', label: 'Asian (Female)' },
  { value: 'european_male', label: 'European (Male)' },
  { value: 'european_female', label: 'European (Female)' },
  { value: 'africa_male', label: 'African (Male)' },
];
const modelLabels = Object.fromEntries(
  modelOptions.map((o) => [o.value, o.label])
);

function toggleDropdown(type) {
  openDropdown.value = openDropdown.value === type ? null : type;
}
function selectOption(type, value) {
  if (type === 'model') selectedModel.value = value;
  if (type === 'count') imageCount.value = value;
  openDropdown.value = null;
}

// API calls
async function generateImage() {
  loading.value = true;
  error.value = null;
  imageUrls.value = [];
  imageNames.value = [];

  try {
    const response = await axios.post('http://127.0.0.1:8000/api/generate', {
      model: selectedModel.value,
      count: imageCount.value,
    });

    if (response.data.status === 'success') {
      imageUrls.value = response.data.image_urls || [];
      imageNames.value = response.data.image_names || [];
    } else {
      error.value = response.data.message || 'Generation failed.';
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Something went wrong.';
  } finally {
    loading.value = false;
  }
}

async function downloadAll() {
  try {
    const response = await downloadZip(imageNames.value);
    const blob = new Blob([response.data], { type: 'application/zip' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = 'generated_images.zip';
    link.click();
    window.URL.revokeObjectURL(link.href);
  } catch (err) {
    console.error('Download all failed', err);
    alert('Failed to download ZIP');
  }
}
</script>

<style scoped>
/* =====================
   Layout & Container
===================== */
.generator-box {
  background: #2a3247;
  width: 100%;
  max-width: 1600px;
  margin: 0 auto;
  padding: 40px;
  text-align: center;
  border-radius: 12px;
  position: relative;
  overflow: visible;
}

/* Add glowing animated border */
.generator-box::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 12px;
  padding: 2px; /* border thickness */
  background: linear-gradient(270deg, #00f0ff, #0077ff, #ff00ff, #00f0ff);
  background-size: 600% 600%;
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  animation: glowing-border 6s linear infinite;
  pointer-events: none;
}

/* Keyframes to animate gradient around border */
@keyframes glowing-border {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}
/* =====================
   Typography
===================== */
h1 {
  font-size: 3.5rem;
  color: #00e5ff;
  margin-bottom: 15px;
  text-shadow: 0 0 10px rgba(0, 229, 255, 0.8), 0 0 20px rgba(0, 229, 255, 0.5);
}
/* =====================
   Form Controls
===================== */
select {
  width: 100%;
  max-width: 280px;

  padding: 10px 14px;
  margin-bottom: 20px;

  font-size: 1rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  text-align: center;
}

/* Generate Button (Cyan Glow) */
button {
  position: relative;
  display: inline-block;
  padding: 12px 24px;
  margin-bottom: 20px;
  font-size: 1.1rem;
  font-weight: bold;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  background: linear-gradient(90deg, #00f0ff, #0077ff);
  box-shadow: 0 0 15px rgba(0, 240, 255, 0.7), 0 0 25px rgba(0, 119, 255, 0.6);
  transition: all 0.3s ease-in-out;
}

button:hover:enabled {
  box-shadow: 0 0 20px #00f0ff, 0 0 40px #0077ff, 0 0 60px #00f0ff;
  transform: translateY(-2px);
}

/* Disabled button styling */
button:disabled {
  background: #444;
  color: #aaa;
  box-shadow: none;
  cursor: not-allowed;
}

button,
.download-btn,
.btn-all {
  position: relative;
  display: inline-block;
  padding: 12px 24px;
  margin-bottom: 20px;
  font-size: 1.1rem;
  font-weight: bold;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
  background: linear-gradient(90deg, #0ff 0%, #f0f 100%);
  box-shadow: 0 0 15px rgba(0, 255, 255, 0.7), 0 0 25px rgba(255, 0, 255, 0.6);
  transition: all 0.3s ease-in-out;
}

/* Hover Glow Boost */
button:hover,
.download-btn:hover,
.btn-all:hover {
  box-shadow: 0 0 20px #0ff, 0 0 40px #f0f, 0 0 60px #0ff;
  transform: translateY(-2px);
}

/* Results Section */
.result {
  margin-top: 2rem;
  width: 100%;
  text-align: center;
}

.result h3 {
  font-size: 1.8rem; /* bigger and more visible */
  font-weight: bold;
  color: #a855f7; /* switch to neon purple */
  text-shadow: 0 0 6px rgba(168, 85, 247, 0.5);
  margin-bottom: 30px; /* space before the grid */
  text-transform: uppercase;
  letter-spacing: 1px;
  border-bottom: 2px solid #a855f7;
  display: inline-block; /* so underline only fits text */
  padding-bottom: 5px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(
    3,
    1fr
  ); /* ✅ fixed 3 images per row on wide screens */
  gap: 30px; /* ✅ bigger gap between cards */
  justify-items: center; /* center each item in its grid cell */
}

.image-card {
  text-align: center;
}

.result img {
  width: 150px; /* ✅ make images bigger */
  height: auto;
  margin: 0 auto 10px;
  display: block;
  border: 3px solid #00aaff;
  border-radius: 10px;
}

/* Preview Button (Green Glow) */
.download-btn {
  display: inline-block;
  margin-top: 10px;
  padding: 8px 16px;
  font-size: 1rem;
  font-weight: bold;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  background: linear-gradient(90deg, #00ff9d, #00cc66);
  box-shadow: 0 0 15px rgba(0, 255, 157, 0.7), 0 0 25px rgba(0, 204, 102, 0.6);
  transition: all 0.3s ease-in-out;
}

.download-btn:hover {
  box-shadow: 0 0 20px #00ff9d, 0 0 40px #00cc66, 0 0 60px #00ff9d;
  transform: translateY(-2px);
}

.download-all {
  margin-top: 40px; /* ✅ extra gap before the ZIP download button */
}

/* Download All Button (Magenta Glow) */
.btn-all {
  padding: 14px 28px;
  font-size: 1.1rem;
  font-weight: bold;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  background: linear-gradient(90deg, #ff00ff, #ff0080);
  box-shadow: 0 0 15px rgba(255, 0, 255, 0.7), 0 0 25px rgba(255, 0, 128, 0.6);
  transition: all 0.3s ease-in-out;
}
.btn-all:hover {
  box-shadow: 0 0 20px #ff00ff, 0 0 40px #ff0080, 0 0 60px #ff00ff;
  transform: translateY(-2px);
}

.dropdown {
  position: relative;
  width: 260px;
  margin: 30px auto;
  text-align: center;
}

.dropdown-label {
  display: block;
  margin-bottom: 6px;
  font-size: 1.2rem;
  font-weight: bold;
  color: #ccc;
}

.dropdown-selected {
  padding: 14px;
  background: #1f2937;
  border: 1px solid #00c3ff;
  border-radius: 6px;
  color: #fff;
  font-size: 1.1rem;
  font-weight: bold;
  box-shadow: 0 0 5px rgba(0, 195, 255, 0.5);
  position: relative;
  cursor: pointer;
  text-align: center;
}

.arrow {
  position: absolute;
  right: 15px;
  top: 50%;
  width: 8px;
  height: 8px;
  border-left: 2px solid #00c3ff;
  border-bottom: 2px solid #00c3ff;
  transform: rotate(-45deg) translateY(-50%);
  transition: transform 0.3s ease;
}

.dropdown.open .arrow {
  transform: rotate(135deg) translateY(-50%);
}

.dropdown-menu {
  position: absolute;
  top: 105%;
  left: 0;
  width: 100%;
  background: #2a3247;
  border: 1px solid #00c3ff;
  border-radius: 6px;
  margin-top: 6px;
  padding: 0;
  list-style: none;
  opacity: 1;
  transform: scaleY(1);
  transition: all 0.25s ease;
  z-index: 10;
}
.dropdown.open .dropdown-menu {
  max-height: 300px;
  opacity: 1;
  transform: scaleY(1);
}

.dropdown-menu li {
  padding: 12px;
  color: #fff;
  cursor: pointer;
  transition: background 0.2s;
}
.dropdown-menu li:hover {
  background: #00c3ff;
  color: #000;
  font-weight: bold;
}

/* Add scroll only to count dropdown */
.dropdown-menu.scrollable {
  max-height: 150px;
  overflow-y: auto;
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

/* ✅ Responsive tweaks */
@media (max-width: 1024px) {
  .grid {
    grid-template-columns: repeat(2, 1fr); /* 2 per row on tablets */
  }
}

@media (max-width: 600px) {
  .grid {
    grid-template-columns: 1fr; /* 1 per row on small mobile */
  }

  .result img {
    width: 160px; /* shrink image for mobile */
  }
}
</style>
