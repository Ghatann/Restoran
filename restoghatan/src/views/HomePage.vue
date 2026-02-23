<template>
  <ion-page>
    <ion-content :fullscreen="true" class="home-content">
      
      <!-- Top Section with Gradient -->
      <div class="header-container">
        
        <!-- Header Top: Logo & Logout -->
        <div class="header-top">
          <div class="brand">
             <div class="brand-icon">
               <ion-icon :icon="restaurantOutline"></ion-icon>
             </div>
             <span class="brand-text">{{ panelTitle }}</span>
          </div>
          <button class="logout-btn" @click="logout">
            <ion-icon :icon="logOutOutline"></ion-icon>
          </button>
        </div>
        
        <!-- Welcome Text -->
        <div class="welcome-section">
          <span class="subtitle">Halo, {{ userRole }}, Selamat Datang</span>
          <h1 class="username">{{ userName }}</h1>
        </div>

      </div>

      <!-- Main Content Area -->
      <div class="main-body">
        <h3 class="section-heading">Menu Utama</h3>
        
        <div class="menu-grid">
          
          <div 
            v-for="item in filteredMenuItems" 
            :key="item.id" 
            class="action-card" 
            @click="item.action()"
          >
            <div :class="['card-icon', item.color]">
              <ion-icon :icon="item.icon"></ion-icon>
            </div>
            <div class="card-text">
              <h4>{{ item.title }}</h4>
              <p>{{ item.sub }}</p>
            </div>
            <ion-icon :icon="chevronForwardOutline" class="arrow-icon"></ion-icon>
          </div>

        </div>
      </div>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage, IonContent, IonIcon
} from '@ionic/vue'
import {
  restaurantOutline, logOutOutline, cartOutline, fastFoodOutline,
  gridOutline, walletOutline, chevronForwardOutline
} from 'ionicons/icons'

import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const userName = ref('User')
const userRole = ref('Admin')

const panelTitle = computed(() => {
  const role = userRole.value.toLowerCase()
  if (role === 'kasir') return 'Kasir Panel'
  if (role === 'chef') return 'Chef Panel'
  return 'Admin Panel'
})

const filteredMenuItems = computed(() => {
  const role = userRole.value.toLowerCase()
  if (role === 'admin') {
    return [
      { id: 'menu', title: 'Menu Makanan', sub: 'Kelola menu', icon: fastFoodOutline, color: 'purple', action: () => router.push('/menu') },
      { id: 'meja', title: 'Atur Meja', sub: 'Layout Resto', icon: gridOutline, color: 'green', action: () => router.push('/meja') }
    ]
  } else if (role === 'kasir') {
    return [
      { id: 'order', title: 'Pesanan Baru', sub: 'Buat pesanan', icon: cartOutline, color: 'blue', action: () => router.push('/order') },
      { id: 'pembayaran', title: 'Kasir', sub: 'Pembayaran', icon: walletOutline, color: 'orange', action: () => router.push('/pembayaran') }
    ]
  } else if (role === 'chef') {
    return [
      { id: 'chef', title: 'Dapur (Chef)', sub: 'Kelola Pesanan', icon: restaurantOutline, color: 'red', action: () => router.push('/chef') }
    ]
  }
  return []
})

onMounted(async () => {
  const user = localStorage.getItem('user')
  if (!user) {
    router.replace('/login')
    return
  }
  
  try {
    const userData = JSON.parse(user)
    userName.value = userData.nama_karyawan || userData.username || 'Admin'
    userRole.value = userData.role || 'Admin'
  } catch {
    userName.value = 'Admin'
    userRole.value = 'Admin'
  }
})



const logout = () => {
  localStorage.removeItem('user')
  router.replace('/login')
}
</script>

<style scoped>
.home-content {
  --background: #f4f6f8;
}

/* Header Container */
.header-container {
  background: white;
  padding: 20px 24px 30px;
  border-radius: 0 0 30px 30px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-icon {
  width: 40px;
  height: 40px;
  background: #667eea;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.brand-icon ion-icon {
  font-size: 22px;
}

.brand-text {
  font-size: 18px;
  font-weight: 800;
  color: #2d3748;
}

.logout-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: #f7fafc;
  border: none;
  color: #718096;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

/* Welcome Section */
.welcome-section {
  margin-bottom: 24px;
}

.subtitle {
  font-size: 14px;
  color: #718096;
  display: block;
  margin-bottom: 4px;
  text-transform: capitalize;
}

.username {
  font-size: 26px;
  font-weight: 800;
  color: #1a202c;
  margin: 0;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.stat-item {
  background: #f7fafc;
  padding: 16px 8px;
  border-radius: 16px;
  text-align: center;
  border: 1px solid #edf2f7;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 800;
  color: #2d3748;
  margin-bottom: 4px;
}

.stat-value.success { color: #48bb78; }
.stat-value.danger { color: #f56565; }

.stat-label {
  font-size: 11px;
  color: #a0aec0;
  font-weight: 600;
  text-transform: uppercase;
}

/* Main Body */
.main-body {
  padding: 24px;
}

.section-heading {
  font-size: 18px;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 16px;
}

/* Menu Grid */
.menu-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

.action-card {
  background: white;
  padding: 16px;
  border-radius: 20px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.03);
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: transform 0.2s;
}

.action-card:active {
  transform: scale(0.98);
}

.card-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.card-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.card-icon.purple { background: linear-gradient(135deg, #d53f8c 0%, #b83280 100%); }
.card-icon.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); }
.card-icon.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); }
.card-icon.red { background: linear-gradient(135deg, #f56565 0%, #c53030 100%); }

.card-text {
  flex: 1;
}

.card-text h4 {
  margin: 0 0 4px;
  font-size: 16px;
  font-weight: 700;
  color: #2d3748;
}

.card-text p {
  margin: 0;
  font-size: 13px;
  color: #718096;
}

.arrow-icon {
  color: #cbd5e0;
  font-size: 20px;
}

</style>
