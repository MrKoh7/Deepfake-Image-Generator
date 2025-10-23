<template>
  <div class="login-box">
    <h2 class="neon-title">Login</h2>

    <input v-model="email" type="email" placeholder="Email" />
    <p v-if="fieldErrors.email" class="error">{{ fieldErrors.email[0] }}</p>

    <input v-model="password" type="password" placeholder="Password" />
    <p v-if="fieldErrors.password" class="error">
      {{ fieldErrors.password[0] }}
    </p>
    <button @click="login" :disabled="loading">
      <span v-if="loading">Loading...</span>
      <span v-else>Login</span>
    </button>

    <p v-if="error" class="error">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, initCsrf } from '../api';

const email = ref('');
const password = ref('');
const error = ref(null);
const fieldErrors = ref({});
const loading = ref(false);
const router = useRouter();

async function login() {
  error.value = null;
  fieldErrors.value = {};
  loading.value = true;

  try {
    await initCsrf();
    const res = await api.post('/login', {
      email: email.value,
      password: password.value,
    });

    localStorage.setItem('user', JSON.stringify(res.data));
    window.dispatchEvent(new Event('storage'));
    router.replace('/');
  } catch (err) {
    if (err.response?.status === 422 && err.response.data.errors) {
      fieldErrors.value = err.response.data.errors;
    } else {
      error.value = err.response?.data?.message || 'Login failed';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* ====== Full Page Wrapper ====== */
.login-page {
  min-height: calc(100vh - 80px); /* ✅ subtract navbar height */
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
  box-sizing: border-box;
}

/* ====== Login Box ====== */
.login-box {
  width: 100%;
  max-width: 480px;
  padding: 3rem 2.5rem;
  background: rgba(20, 22, 34, 0.88); /* ✅ glass effect */
  border-radius: 16px;
  text-align: center;
  color: #fff;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(12px);
  margin: auto;
}

/* ✅ Glowing animated border */
.login-box::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 16px;
  padding: 3px;
  background: linear-gradient(270deg, #00f0ff, #0077ff, #ff00ff, #00f0ff);
  background-size: 600% 600%;
  animation: glowing-border 6s linear infinite;
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events: none;
}

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

/* Title */
.neon-title {
  font-size: 2.3rem;
  margin-bottom: 2rem;
  color: #00e5ff;
  text-shadow: 0 0 10px #00e5ff, 0 0 20px #0077ff, 0 0 30px #00f0ff;
}

/* Inputs */
input {
  width: 100%;
  padding: 15px;
  margin: 14px 0;
  border-radius: 8px;
  border: 1px solid #00c3ff;
  background: #1f2937;
  color: #fff;
  font-size: 1.05rem;
  outline: none;
  box-shadow: 0 0 6px rgba(0, 195, 255, 0.5);
  transition: all 0.2s ease-in-out;
}
input:focus {
  border-color: #ff00ff;
  box-shadow: 0 0 12px #ff00ff, 0 0 20px #00f0ff;
}

/* Button */
button {
  width: 100%;
  padding: 15px;
  margin-top: 20px;
  font-size: 1.2rem;
  font-weight: bold;
  background: linear-gradient(90deg, #00f0ff, #ff00ff);
  border: none;
  border-radius: 8px;
  color: #fff;
  cursor: pointer;
  box-shadow: 0 0 15px #00f0ff, 0 0 25px #ff00ff;
  transition: all 0.3s ease-in-out;
}
button:hover:enabled {
  transform: translateY(-2px);
  box-shadow: 0 0 20px #00f0ff, 0 0 40px #ff00ff, 0 0 60px #00f0ff;
}
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: #333;
  box-shadow: none;
}

/* Error text */
.error {
  color: #ff5555;
  margin-top: 15px;
  font-size: 1rem;
  text-shadow: 0 0 6px rgba(255, 85, 85, 0.6);
}

/* Responsive fix for very small screens */
@media (max-height: 600px) {
  .login-page {
    align-items: flex-start;
    padding-top: 100px;
  }
}
</style>
