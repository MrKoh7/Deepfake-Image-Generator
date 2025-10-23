<template>
  <div class="register-page">
    <div class="register-box">
      <h2 class="neon-title">Sign Up</h2>
      <form @submit.prevent="register">
        <!-- Name -->
        <input v-model="name" type="text" placeholder="Name" />
        <p v-if="errors.name" class="error">{{ errors.name[0] }}</p>

        <!-- Email -->
        <input v-model="email" type="email" placeholder="Email" />
        <p v-if="errors.email" class="error">{{ errors.email[0] }}</p>

        <!-- Password -->
        <input v-model="password" type="password" placeholder="Password" />
        <p v-if="errors.password" class="error">{{ errors.password[0] }}</p>

        <!-- Submit -->
        <button type="submit" :disabled="loading">
          <span v-if="loading">Registering...</span>
          <span v-else>Register</span>
        </button>
      </form>

      <!-- General message -->
      <p v-if="message" class="msg">{{ message }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const name = ref('')
const email = ref('')
const password = ref('')
const message = ref('')
const errors = ref({}) // ✅ store validation errors
const loading = ref(false)
const router = useRouter()

async function register() {
  loading.value = true
  errors.value = {} // reset errors
  message.value = ''

  try {
    const res = await axios.post(
      'http://127.0.0.1:8000/api/register',
      { name: name.value, email: email.value, password: password.value },
      { withCredentials: true }
    )

    message.value = '✅ Registered as ' + res.data.name

    // Save user session
    localStorage.setItem('user', JSON.stringify(res.data))
    window.dispatchEvent(new Event('storage'))

    router.replace('/')
  } catch (err) {
    if (err.response?.status === 422 && err.response.data.errors) {
      errors.value = err.response.data.errors // ✅ show detailed validation errors
    } else {
      message.value = '❌ ' + (err.response?.data?.message || err.message)
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>

/* ====== Centered & Bigger ====== */
/* ====== Full Page Wrapper ====== */
/* ====== Full Page Wrapper ====== */
.register-page {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  overflow-y: auto; /* ✅ only scrolls if form is taller than screen */
}

/* ====== Register Box ====== */
.register-box {
  width: 100%;
  max-width: 480px;
  padding: 3rem 2.5rem;
  background: rgba(20, 22, 34, 0.88);
  border-radius: 16px;
  text-align: center;
  color: #fff;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(12px);
  margin: auto;
}



/* ✅ Glowing animated border */
.register-box::before {
  content: "";
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
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Title */
.neon-title {
  font-size: 2.5rem;
  margin-bottom: 2rem;
  color: #00e5ff;
  text-shadow: 0 0 10px #00e5ff, 0 0 20px #0077ff, 0 0 30px #00f0ff;
}

/* Inputs */
input {
  width: 100%;
  padding: 16px;
  margin: 14px 0;
  border-radius: 8px;
  border: 1px solid #00c3ff;
  background: #1f2937;
  color: #fff;
  font-size: 1.1rem;
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
  padding: 16px;
  margin-top: 15px;
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

/* Message */
.error {
  color: #ff5555;
  font-size: 0.9rem;
  margin-top: -10px;
  margin-bottom: 10px;
  text-align: left;
  text-shadow: 0 0 6px rgba(255, 85, 85, 0.6);
}
</style>
