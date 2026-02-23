<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar class="custom-toolbar">
        <ion-buttons slot="start">
          <ion-back-button default-href="/home" color="primary" text=""></ion-back-button>
        </ion-buttons>
        <ion-title class="page-title">Pesanan Baru</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="main-content">
      <!-- Stepper Indicator -->
      <div class="stepper">
        <div class="step-item" :class="{ active: step >= 1 }">
          <div class="step-circle">1</div>
          <span>Meja</span>
        </div>
        <div class="step-line" :class="{ active: step >= 2 }"></div>
        <div class="step-item" :class="{ active: step >= 2 }">
          <div class="step-circle">2</div>
          <span>Menu</span>
        </div>
      </div>
      
      <!-- Step 1: Pilih Meja -->
      <div v-if="step === 1" class="step-container fade-in">
        <div class="customer-name-section">
          <label class="section-label">Nama Pelanggan (Opsional)</label>
          <div class="search-box">
            <ion-icon :icon="peopleOutline"></ion-icon>
            <input 
              v-model="namaKonsumen" 
              type="text" 
              placeholder="Masukkan nama pelanggan..." 
              class="native-input"
            />
          </div>
        </div>

        <h2 class="section-header">Pilih Meja Pelanggan</h2>
        
        <div v-if="tables.length === 0" class="empty-state">
          <ion-icon :icon="alertCircleOutline"></ion-icon>
          <p>Belum ada meja tersedia</p>
        </div>

        <div class="table-grid">
          <div 
            v-for="table in tables" 
            :key="table.id"
            class="table-card ion-activatable ripple-parent"
            :class="{ 
              'selected': selectedTable?.id === table.id,
              'occupied': table.status === 'booking'
            }"
            @click="selectTable(table)"
          >
            <div class="card-status-indicator"></div>
            <div class="table-icon">
              <ion-icon :icon="table.status === 'booking' ? peopleOutline : happyOutline"></ion-icon>
            </div>
            <h3 class="table-code">{{ table.nomor_meja }}</h3>
            <p class="table-cap">{{ table.kapasitas }} Kursi</p>
            <span class="status-badge" :class="table.status">
              {{ table.status === 'booking' ? 'Terisi' : 'Tersedia' }}
            </span>
            <ion-ripple-effect></ion-ripple-effect>
          </div>
        </div>
      </div>

      <!-- Step 2: Pilih Menu -->
      <div v-else class="step-container fade-in">
        <h2 class="section-header">Pilih Menu Makanan</h2>
        
        <div class="search-box">
          <ion-icon :icon="searchOutline"></ion-icon>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Cari nasi goreng, mendoan..." 
            class="native-input"
          />
        </div>
        
        <div class="menu-list">
          <div v-for="menu in filteredMenus" :key="menu.id" class="menu-item">
            <div class="menu-item-left">
              <div class="menu-thumbnail">
                <img v-if="menu.foto" :src="'http://localhost/restoo/Api_Mobile/uploads/' + menu.foto" class="thumbnail-img" />
                <ion-icon v-else :icon="fastFoodOutline" class="placeholder-icon"></ion-icon>
              </div>
              <div class="menu-info">
                <h3 class="menu-name">{{ menu.nama_menu }}</h3>
                <div class="menu-price-row">
                  <span class="menu-price">Rp {{ formatPrice(menu.harga) }}</span>
                  <span class="menu-stock-badge">Stok: {{ menu.stok_porsi }}</span>
                </div>
              </div>
            </div>
            
            <div class="menu-action">
              <div v-if="menu.status === 'available'" class="qty-group">
                <button class="qty-btn minus" @click="updateQty(menu, -1)" :disabled="getQty(menu.id) === 0">
                  <ion-icon :icon="removeOutline"></ion-icon>
                </button>
                <span class="qty-val">{{ getQty(menu.id) }}</span>
                <button class="qty-btn plus" @click="updateQty(menu, 1)">
                  <ion-icon :icon="addOutline"></ion-icon>
                </button>
              </div>
              <span v-else class="sold-out-badge">HABIS</span>
            </div>
          </div>
        </div>
      </div>

    </ion-content>

    <!-- Footer Actions -->
    <ion-footer class="ion-no-border custom-footer">
      <div v-if="step === 1" class="footer-padding">
         <button class="primary-btn full-width" :disabled="!selectedTable" @click="nextStep">
           Lanjut Pilih Menu
           <ion-icon :icon="arrowForwardOutline"></ion-icon>
         </button>
      </div>
      
      <div v-if="step === 2" class="cart-panel">
         <div class="cart-info">
           <span class="label">Total Pembayaran</span>
           <span class="value">Rp {{ formatPrice(grandTotal) }}</span>
           <span class="item-count">{{ totalItems }} Item dipilih</span>
         </div>
         <button class="primary-btn pay-btn" :disabled="totalItems === 0" @click="submitOrder">
           <ion-spinner v-if="submitting" name="crescent"></ion-spinner>
           <span v-else>Proses Order</span>
         </button>
      </div>
    </ion-footer>
    
    <ion-toast :is-open="toast.show" :message="toast.msg" :color="toast.color" :duration="2000" @didDismiss="toast.show = false"></ion-toast>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent,
  IonFooter, IonIcon, IonSpinner, IonToast, IonRippleEffect
} from '@ionic/vue'
import { 
  searchOutline, removeOutline, addOutline, arrowForwardOutline,
  alertCircleOutline, peopleOutline, happyOutline, fastFoodOutline
} from 'ionicons/icons'
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const step = ref(1)
const tables = ref<any[]>([])
const menus = ref<any[]>([])
const cart = reactive<any>({})
const selectedTable = ref<any>(null)
const searchQuery = ref('')
const namaKonsumen = ref('')
const submitting = ref(false)
const toast = reactive({ show: false, msg: '', color: 'success' })

const API_MEJA  = 'http://localhost/restoo/Api_Mobile/mejas.php'
const API_MENU  = 'http://localhost/restoo/Api_Mobile/menus.php'
const API_ORDER = 'http://localhost/restoo/Api_Mobile/orderans.php'

const filteredMenus = computed(() => {
  return menus.value.filter(m => m.nama_menu.toLowerCase().includes(searchQuery.value.toLowerCase()))
})

const totalItems = computed(() => Object.values(cart).reduce((a: any, b: any) => a + b.qty, 0))
const grandTotal = computed<number>(() => {
  return Object.values(cart).reduce((sum: number, item: any) => sum + (item.price * item.qty), 0)
})

const loadData = async () => {
  try {
    const [resMeja, resMenu] = await Promise.all([
      fetch(API_MEJA).then(r => r.json()),
      fetch(API_MENU).then(r => r.json())
    ])
    
    if (resMeja.status === 'success') tables.value = resMeja.data
    if (resMenu.status === 'success') menus.value = resMenu.data
  } catch (e) {
    console.error(e)
  }
}

const selectTable = (table: any) => {
  if (table.status === 'booking') {
    toast.msg = 'Meja ini sedang terisi!'
    toast.color = 'warning'
    toast.show = true
    return
  }
  selectedTable.value = table
}

const nextStep = () => {
    step.value = 2
}

const getQty = (id: number | string) => cart[Number(id)]?.qty || 0

const updateQty = (menu: any, change: number) => {
  const menuId = parseInt(menu.id)
  const current = getQty(menuId)
  const newQty = current + change
  
  if (newQty <= 0) {
    delete cart[menuId]
  } else {
    // cek stok
    if (change > 0 && newQty > menu.stok_porsi) {
        toast.msg = `Stok tersisa ${menu.stok_porsi}`
        toast.color = 'danger'
        toast.show = true
        return
    }
    
    cart[menuId] = {
      id: menuId,
      qty: newQty,
      price: parseInt(menu.harga)
    }
  }
}

const formatPrice = (p: number) => p.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')

const submitOrder = async () => {
  submitting.value = true
  
  const items = Object.values(cart).map((c:any) => ({
    id_menu: c.id,
    jumlah: c.qty,
    catatan: ''
  }))
  
  const userData = JSON.parse(localStorage.getItem('user') || '{}')

  try {
    const res = await fetch(API_ORDER, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_meja: selectedTable.value.id,
        id_user: userData.id ?? null,
        nama_konsumen: namaKonsumen.value || null,
        items,
        mode_pesanan: 'dinein'
      })
    })
    const data = await res.json()
    
    if (data.status === 'success') {
      toast.msg = 'Pesanan Berhasil!'
      toast.color = 'success'
      toast.show = true
      setTimeout(() => router.replace('/home'), 1000)
    } else {
      toast.msg = data.msg
      toast.color = 'danger'
      toast.show = true
    }
  } catch(e) {
    toast.msg = 'Gagal kirim pesanan'
    toast.color = 'danger'
    toast.show = true
  } finally {
    submitting.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
/* Page Layout */
.main-content {
  --background: #f8f9fa;
}

.custom-toolbar {
  --background: white;
  --border-width: 0;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.page-title {
  font-weight: 700;
  color: #1a1a2e;
  font-size: 18px;
}

/* Stepper */
.stepper {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 0;
  background: white;
  margin-bottom: 20px;
}

.step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  position: relative;
  z-index: 2;
}

.step-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e9ecef;
  color: #adb5bd;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s;
}

.step-item.active .step-circle {
  background: #667eea;
  color: white;
  box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
}

.step-item span {
  font-size: 12px;
  font-weight: 500;
  color: #adb5bd;
}

.step-item.active span {
  color: #667eea;
  font-weight: 600;
}

.step-line {
  flex: 0 0 60px;
  height: 2px;
  background: #e9ecef;
  margin: 0 10px 18px; /* adjust for text height */
}

.step-line.active {
  background: #667eea;
}

.step-container {
  padding: 0 20px 40px;
}

.section-header {
  font-size: 20px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 20px;
}

/* Native Search Input (Fix invisible text) */
.search-box {
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0 16px;
  height: 50px;
  margin-bottom: 20px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}

.search-box ion-icon {
  font-size: 20px;
  color: #a0aec0;
  margin-right: 12px;
}

.native-input {
  border: none;
  background: transparent;
  width: 100%;
  height: 100%;
  font-size: 15px;
  color: #2d3748; /* Dark text */
  outline: none;
}

.native-input::placeholder {
  color: #a0aec0;
}

/* Table Grid */
.table-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.table-card {
  position: relative;
  background: white;
  border-radius: 16px;
  padding: 20px 16px;
  text-align: center;
  border: 2px solid transparent;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  transition: transform 0.2s, box-shadow 0.2s;
  overflow: hidden;
}

.table-card:active {
  transform: scale(0.98);
}

.table-card.selected {
  border-color: #667eea;
  background: #eff6ff;
}

.table-card.occupied {
  opacity: 0.8;
  background: #fff5f5;
  border-color: #fed7d7;
}

.table-icon {
  font-size: 32px;
  color: #a0aec0;
  margin-bottom: 8px;
}

.selected .table-icon { color: #667eea; }
.occupied .table-icon { color: #fc8181; }

.table-code {
  font-size: 22px;
  font-weight: 800;
  color: #2d3748;
  margin: 0 0 4px;
}

.table-cap {
  font-size: 13px;
  color: #718096;
  margin: 0 0 12px;
}

.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.status-badge.available {
  background: #c6f6d5;
  color: #22543d;
}
.status-badge.booking {
  background: #fed7d7;
  color: #742a2a;
}

/* Menu List */
.menu-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.menu-item {
  background: white;
  padding: 16px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.menu-item-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
}

.menu-thumbnail {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: #f7fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.thumbnail-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.placeholder-icon {
  font-size: 24px;
  color: #cbd5e0;
}

.menu-info {
  flex: 1;
}

.menu-name {
  font-size: 16px;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 4px;
}

.menu-price-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.menu-price {
  font-size: 15px;
  font-weight: 700;
  color: #667eea;
  margin: 0;
}

.menu-stock-badge {
  font-size: 11px;
  background: #f1f5f9;
  color: #64748b;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 600;
}

.menu-action {
  margin-left: 16px;
}

.qty-group {
  display: flex;
  align-items: center;
  background: #f7fafc;
  border-radius: 8px;
  padding: 4px;
}

.qty-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s;
}

.qty-btn.minus {
  background: white;
  color: #e53e3e;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.qty-btn.plus {
  background: #667eea;
  color: white;
  box-shadow: 0 2px 4px rgba(102, 126, 234, 0.4);
}

.qty-btn:disabled {
  background: #edf2f7;
  color: #cbd5e0;
  cursor: not-allowed;
  box-shadow: none;
}

.qty-val {
  width: 32px;
  text-align: center;
  font-weight: 700;
  font-size: 15px;
  color: #2d3748;
}

.sold-out-badge {
  background: #fed7d7;
  color: #c53030;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}

/* Footer & Buttons */
.custom-footer {
  background: white;
  box-shadow: 0 -4px 20px rgba(0,0,0,0.05);
}

.footer-padding {
  padding: 16px 20px;
}

.cart-panel {
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.cart-info {
  display: flex;
  flex-direction: column;
}

.cart-info .label {
  font-size: 12px;
  color: #718096;
}

.cart-info .value {
  font-size: 20px;
  font-weight: 800;
  color: #2d3748;
  line-height: 1.2;
}

.cart-info .item-count {
  font-size: 12px;
  color: #667eea;
  font-weight: 500;
}

.primary-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  padding: 0 24px;
  height: 48px;
  font-size: 15px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  transition: transform 0.2s;
}

.primary-btn:active {
  transform: scale(0.96);
}

.primary-btn:disabled {
  background: #cbd5e0;
  box-shadow: none;
  cursor: not-allowed;
}

.primary-btn.full-width {
  width: 100%;
  justify-content: center;
}

/* Fade In Animation */
.fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
