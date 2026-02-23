<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar class="chef-toolbar">
        <ion-buttons slot="start">
          <ion-back-button default-href="/home" color="light" text=""></ion-back-button>
        </ion-buttons>
        <ion-title>Chef Panel</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="loadOrders">
            <ion-icon :icon="refreshOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="chef-content">
      <div class="container">
        <div v-if="loading" class="center-msg">
          <ion-spinner name="crescent" color="primary"></ion-spinner>
        </div>

        <div v-else-if="groupedOrders.length === 0" class="empty-msg">
          <ion-icon :icon="restaurantOutline"></ion-icon>
          <h3>Sudah Selesai Semua!</h3>
          <p>Tidak ada pesanan aktif saat ini.</p>
        </div>

        <div v-else class="order-grid">
          <div v-for="order in groupedOrders" :key="order.id" class="order-card">
            <div class="card-header">
              <div class="header-left">
                <h4 class="customer-name">{{ order.nama_konsumen || 'Tanpa Nama' }} (Meja {{ order.nomor_meja || '?' }})</h4>
                <p class="order-time">Waktu: {{ formatTime(order.tanggal_orderan) }}</p>
              </div>
              <div class="timer-badge">
                <ion-icon :icon="timerOutline"></ion-icon>
                <span>{{ order.age }}</span>
              </div>
            </div>

            <div class="item-list">
              <div v-for="item in order.items" :key="item.id" class="food-item">
                <div class="item-info">
                  <img v-if="item.foto" :src="'http://localhost/restoo/Api_Mobile/uploads/' + item.foto" class="item-img" />
                  <div class="item-text">
                    <span class="item-title">{{ item.nama_menu }} <span class="qty">x {{ item.jumlah }}</span></span>
                    <p v-if="item.catatan" class="item-note">Note: {{ item.catatan }}</p>
                  </div>
                </div>
                <button 
                  class="done-btn" 
                  @click="markDone(item.id)" 
                  :disabled="updating === item.id"
                >
                  <ion-spinner v-if="updating === item.id" name="lines" size="small"></ion-spinner>
                  <span v-else>SELESAI</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>

    <ion-toast 
      :is-open="toast.show" 
      message="Pesanan telah selesai disiapkan!" 
      :duration="2000" 
      color="success"
      @didDismiss="toast.show = false"
    ></ion-toast>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle,
  IonContent, IonIcon, IonSpinner, IonToast
} from '@ionic/vue'
import {
  refreshOutline, restaurantOutline, timerOutline
} from 'ionicons/icons'
import { ref, onMounted, computed } from 'vue'

const API_DETAIL = 'http://localhost/restoo/Api_Mobile/detail_orderans.php'
const API_ORDER = 'http://localhost/restoo/Api_Mobile/orderans.php'

const items = ref<any[]>([])
const allOrders = ref<any[]>([])
const loading = ref(false)
const updating = ref<number | null>(null)
const toast = ref({ show: false })

// Load all details and order headers
const loadOrders = async () => {
  loading.value = true
  try {
    // 1. Get all detail items
    const resD = await fetch(API_DETAIL)
    const dataD = await resD.json()
    
    // 2. Get all order headers to get table names and customer names
    const resO = await fetch(API_ORDER)
    const dataO = await resO.json()
    
    if (dataD.status === 'success' && dataO.status === 'success') {
      items.value = dataD.data.filter((i: any) => i.status === 'processing')
      allOrders.value = dataO.data
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const groupedOrders = computed<any[]>(() => {
  const groups: Record<number, any> = {}
  
  items.value.forEach(item => {
    if (!groups[item.id_orderan]) {
      const parent = allOrders.value.find(o => o.id === item.id_orderan) || {}
      groups[item.id_orderan] = {
        id: item.id_orderan,
        nomor_meja: parent.nomor_meja,
        nama_konsumen: parent.nama_konsumen,
        tanggal_orderan: parent.tanggal_orderan,
        age: calculateAge(parent.tanggal_orderan),
        items: []
      }
    }
    groups[item.id_orderan].items.push(item)
  })
  
  return Object.values(groups).sort((a: any, b: any) => 
    new Date(a.tanggal_orderan).getTime() - new Date(b.tanggal_orderan).getTime()
  )
})

const calculateAge = (timeStr: string) => {
  if (!timeStr) return '0m'
  const diff = Date.now() - new Date(timeStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 60) return `${mins}m`
  return `${Math.floor(mins/60)}h ${mins%60}m`
}

const formatTime = (timeStr: string) => {
  if (!timeStr) return '--:--'
  const d = new Date(timeStr)
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

const markDone = async (id: number) => {
  updating.value = id
  try {
    const res = await fetch(API_DETAIL, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status: 'done' })
    })
    const data = await res.json()
    if (data.status === 'success') {
      // Remove from local list
      items.value = items.value.filter(i => i.id !== id)
      toast.value.show = true
    }
  } catch (err) {
    console.error(err)
  } finally {
    updating.value = null
  }
}

onMounted(loadOrders)
</script>

<style scoped>
.chef-toolbar {
  --background: #3182ce;
  --color: white;
}

.chef-content {
  --background: #1a202c;
  color: #e2e8f0;
}

.container {
  padding: 20px;
}

.center-msg, .empty-msg {
  text-align: center;
  padding: 100px 20px;
}

.empty-msg ion-icon {
  font-size: 64px;
  color: #4a5568;
  margin-bottom: 20px;
}

.order-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.order-card {
  background: #2d3748;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}

.card-header {
  padding: 16px;
  background: #283141;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid #4a5568;
}

.table-label {
  font-size: 11px;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.customer-name {
  margin: 4px 0;
  font-size: 20px;
  font-weight: 700;
  color: #fff;
}

.order-time {
  margin: 0;
  font-size: 13px;
  color: #718096;
}

.timer-badge {
  background: #f6e05e;
  color: #744210;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 4px;
}

.item-list {
  padding: 8px 16px;
}

.food-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #4a556822;
}

.food-item:last-child {
  border-bottom: none;
}

.item-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.item-img {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  object-fit: cover;
}

.item-title {
  font-weight: 600;
  font-size: 15px;
}

.qty {
  color: #fc8181;
  margin-left: 4px;
}

.item-note {
  margin: 2px 0 0;
  font-size: 12px;
  color: #a0aec0;
  font-style: italic;
}

.done-btn {
  background: #48bb78;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  min-width: 80px;
}

.done-btn:active {
  background: #38a169;
}

.done-btn:disabled {
  opacity: 0.6;
}
</style>
