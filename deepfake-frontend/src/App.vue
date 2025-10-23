<template>
  <header class="app-header">
    <span class="logo">DeepFakeGEN</span>

    <!-- Hamburger for mobile -->
    <div class="hamburger" @click="toggleMenu" :class="{ open: isOpen }">
      <span></span><span></span><span></span>
    </div>

    <nav class="nav-links" :class="{ show: isOpen }">
      <!-- Always visible -->
      <router-link to="/" exact-active-class="active" @click="closeMenu">
        Deepfake Generators
      </router-link>
      <router-link
        to="/training"
        exact-active-class="active"
        @click="closeMenu"
      >
        Deepfake Generator Training
      </router-link>

      <!-- Auth links -->
      <router-link
        v-if="!user"
        to="/login"
        exact-active-class="active"
        @click="closeMenu"
      >
        Login
      </router-link>
      <router-link
        v-if="!user"
        to="/register"
        exact-active-class="active"
        @click="closeMenu"
      >
        Register
      </router-link>

      <!-- ✅ Show username + logout -->
      <div v-if="user" class="user-info">
        <span class="username">{{ user?.name || user?.email || "User" }}</span>

        <button @click="logout" class="logout-btn">Logout</button>
      </div>
    </nav>
  </header>

  <main class="view-container">
    <router-view />
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const isOpen = ref(false)
const user = ref(null) // reactive user object

function toggleMenu() {
  isOpen.value = !isOpen.value
}
function closeMenu() {
  isOpen.value = false
}

// ✅ Helper to load user from localStorage
function loadUser() {
  const storedUser = localStorage.getItem('user')
  user.value = storedUser ? JSON.parse(storedUser) : null
}

// ✅ Sync user reactively with localStorage
window.addEventListener('storage', () => {
  loadUser()
})

// ✅ Load user on startup
onMounted(() => {
  loadUser()
})

// ✅ Logout
async function logout() {
  try {
    await axios.post('http://127.0.0.1:8000/api/logout', {}, { withCredentials: true })
  } catch (e) {
    console.error('Logout error', e)
  }
  localStorage.removeItem('user')
  user.value = null
  router.push('/login')
}
</script>



<style>
/* ===== HEADER ===== */
.app-header {
  background: rgba(17, 17, 17, 0.92);
  backdrop-filter: blur(12px);
  padding: 20px 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  box-sizing: border-box;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1000;
  border-bottom: 1px solid rgba(0, 195, 255, 0.3);
}

.logo {
  color: #00c3ff;
  font-size: 1.9rem;
  font-weight: bold;
  margin: 0;
  line-height: 1;
  cursor: default;
  text-shadow: 0 0 10px rgba(0, 195, 255, 0.9);
}

/* ===== NAV LINKS ===== */
.nav-links {
  flex: 1;
  display: flex;
  justify-content: center;
  gap: 50px;
  align-items: center;
}

.nav-links a {
  position: relative;
  color: white;
  text-decoration: none;
  font-size: 1.1rem;
  font-weight: 500;
  letter-spacing: 0.5px;
  padding-bottom: 6px;
  transition: color 0.2s;
}

.nav-links a::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  width: 0%;
  height: 2px;
  background-color: #00c3ff;
  transition: width 0.3s ease;
}
.nav-links a.active::after {
  width: 100%;
}


/* ===== LINKS ===== */
a,
.green {
  text-decoration: none;
  color: #00c3ff; /* ✅ neon cyan */
  transition: 0.3s;
  padding: 3px;
}

a:hover {
  background-color: transparent; 
  text-shadow: 0 0 8px #00c3ff, 0 0 16px #00f0ff; /* ✅ glow highlight */
}

.nav-links a:hover {
  color: #00c3ff;
  text-shadow: 0 0 6px #00c3ff;
}

/* ===== USER INFO ===== */
/* ===== USER INFO ===== */
.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 12px;
  background: rgba(0, 195, 255, 0.08); /* subtle glass background */
  border-radius: 12px;
  box-shadow: 0 0 8px rgba(0, 195, 255, 0.3);
}

.username {
  font-size: 1rem;
  font-weight: 600;
  background: linear-gradient(90deg, #00f0ff, #0077ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 0 6px rgba(0, 195, 255, 0.7), 0 0 12px rgba(0, 240, 255, 0.6);
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: default;
}

.username::before {
  font-size: 1.2rem;
  color: #00e5ff;
  text-shadow: 0 0 6px #00f0ff, 0 0 10px #0077ff;
}

.logout-btn {
  background: none;
  border: none;
  color: #ff5555;
  font-size: 0.95rem;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}
.logout-btn:hover {
  color: #ff0000;
  text-shadow: 0 0 6px rgba(255, 0, 0, 0.9);
}


/* ===== HAMBURGER MENU ===== */
.hamburger {
  display: none;
  flex-direction: column;
  justify-content: space-between;
  width: 28px;
  height: 22px;
  cursor: pointer;
  z-index: 1200;
}
.hamburger span {
  display: block;
  height: 3px;
  width: 100%;
  background: white;
  border-radius: 2px;
  transition: 0.3s ease;
}
.hamburger.open span:nth-child(1) {
  transform: translateY(9px) rotate(45deg);
}
.hamburger.open span:nth-child(2) {
  opacity: 0;
}
.hamburger.open span:nth-child(3) {
  transform: translateY(-9px) rotate(-45deg);
}

/* ===== BREAKPOINTS ===== */

/* Large tablets / smaller desktops */
@media (max-width: 1040px) {
  .nav-links {
    gap: 30px;
  }
  .nav-links a {
    font-size: 1rem;
  }
  .logo {
    font-size: 1.7rem;
  }
}

/* Tablets */
@media (max-width: 850px) {
  .nav-links {
    gap: 20px;
  }
  .nav-links a {
    font-size: 0.95rem;
  }
  .logo {
    font-size: 1.6rem;
  }
}

/* Mobile full-screen menu */
@media (max-width: 700px) {
  .hamburger {
    display: flex;
    position: absolute;
    right: 20px;
  }

  .logo {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-size: 1.5rem;
  }

  .nav-links {
    position: fixed; /* ✅ full screen */
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh; /* take full screen height */
    background: rgba(20, 20, 30, 0.97);
    backdrop-filter: blur(14px);
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 30px;
    text-align: center;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: all 0.4s ease;
  }

  .nav-links.show {
    max-height: 100vh;
    opacity: 1;
  }

  .nav-links a {
    font-size: 1.4rem;
    padding: 12px 0;
  }
}

/* ===== MAIN VIEW CONTAINER ===== */
.view-container {
  margin-top: 100px;
}
</style>


