<template>
  <ion-page>
    <ion-content class="login-content" :fullscreen="true">
      <!-- Background Gradient -->
      <div class="gradient-bg"></div>
      
      <!-- Login Container -->
      <div class="login-container">
        <!-- Logo Section -->
        <div class="logo-section">
          <div class="logo-circle">
            <ion-icon :icon="restaurantOutline"></ion-icon>
          </div>
          <h1 class="app-title">RestoGhatan</h1>
          <p class="app-subtitle">Kelola Restoran dengan Mudah</p>
        </div>

        <!-- Login Card -->
        <div class="login-card">
          <h2 class="card-title">Selamat Datang</h2>
          <p class="card-subtitle">Masuk ke akun Anda</p>

          <!-- Form -->
          <div class="form-group">
            <div class="input-wrapper">
              <ion-icon :icon="mailOutline" class="input-icon"></ion-icon>
              <ion-input
                v-model="email"
                type="email"
                placeholder="Email"
                class="custom-input"
              />
            </div>
          </div>

          <div class="form-group">
            <div class="input-wrapper">
              <ion-icon :icon="lockClosedOutline" class="input-icon"></ion-icon>
              <ion-input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Password"
                class="custom-input"
              />
              <ion-icon 
                :icon="showPassword ? eyeOffOutline : eyeOutline" 
                class="toggle-password"
                @click="showPassword = !showPassword"
              ></ion-icon>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="error-message">
            <ion-icon :icon="alertCircleOutline"></ion-icon>
            <span>{{ error }}</span>
          </div>

          <!-- Login Button -->
          <ion-button 
            expand="block" 
            class="login-button" 
            @click="login"
            :disabled="loading"
          >
            <ion-spinner v-if="loading" name="crescent"></ion-spinner>
            <span v-else>Masuk</span>
          </ion-button>
        </div>

        <!-- Footer -->
        <p class="footer-text">© 2026 RestoGhatan. All rights reserved.</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonContent,
  IonInput,
  IonButton,
  IonIcon,
  IonSpinner
} from '@ionic/vue'

import {
  restaurantOutline,
  mailOutline,
  lockClosedOutline,
  eyeOutline,
  eyeOffOutline,
  alertCircleOutline
} from 'ionicons/icons'

import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)

const login = async () => {
  if (!email.value || !password.value) {
    error.value = 'Email dan password harus diisi'
    return
  }

  loading.value = true
  error.value = ''
  
  try {
    const res = await axios.post('http://localhost/restoo/Api_Mobile/login.php', {
      email: email.value,
      password: password.value
    })

    if (res.data.status === 'success') {
      localStorage.setItem('user', JSON.stringify(res.data.data))
      router.push('/home')
    } else {
      error.value = res.data.msg
    }

  } catch (err) {
    error.value = 'Gagal terhubung ke server'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-content {
  --background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  z-index: 0;
}

.gradient-bg::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
  animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(-25%, -25%); }
}

.login-container {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
}

.logo-section {
  text-align: center;
  margin-bottom: 30px;
}

.logo-circle {
  width: 90px;
  height: 90px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.logo-circle ion-icon {
  font-size: 45px;
  color: white;
}

.app-title {
  font-size: 28px;
  font-weight: 700;
  color: white;
  margin: 0;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.app-subtitle {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.85);
  margin: 8px 0 0;
}

.login-card {
  background: white;
  border-radius: 24px;
  padding: 32px 24px;
  width: 100%;
  max-width: 380px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.card-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 4px;
  text-align: center;
}

.card-subtitle {
  font-size: 14px;
  color: #6c757d;
  margin: 0 0 28px;
  text-align: center;
}

.form-group {
  margin-bottom: 18px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: #f8f9fa;
  border-radius: 14px;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.input-wrapper:focus-within {
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.input-icon {
  font-size: 20px;
  color: #adb5bd;
  margin-left: 16px;
}

.custom-input {
  --padding-start: 12px;
  --padding-end: 16px;
  --padding-top: 16px;
  --padding-bottom: 16px;
  --background: transparent;
  --color: #1a1a2e;
  font-size: 16px;
  flex: 1;
}

.toggle-password {
  font-size: 20px;
  color: #adb5bd;
  margin-right: 16px;
  cursor: pointer;
  transition: color 0.2s;
}

.toggle-password:hover {
  color: #667eea;
}

.error-message {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: #fff5f5;
  border: 1px solid #fed7d7;
  border-radius: 12px;
  margin-bottom: 18px;
  color: #c53030;
  font-size: 14px;
}

.error-message ion-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.login-button {
  --background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --background-hover: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
  --border-radius: 14px;
  --box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
  height: 52px;
  font-size: 16px;
  font-weight: 600;
  text-transform: none;
  margin-top: 8px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.login-button:active {
  transform: scale(0.98);
}

.footer-text {
  margin-top: 30px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
  text-align: center;
}
</style>