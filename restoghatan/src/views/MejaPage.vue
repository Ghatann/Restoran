<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar class="custom-toolbar">
        <ion-buttons slot="start">
          <ion-back-button default-href="/home" color="primary" text=""></ion-back-button>
        </ion-buttons>
        <ion-title class="page-title">Atur Meja</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="main-content">
      <div class="content-wrapper">
        
        <!-- Add Card Form -->
        <div class="form-card">
          <h3 class="card-title">Tambah Meja Baru</h3>
          
          <div class="form-group">
            <label>Nomor Meja</label>
            <input 
              v-model="newTableCode" 
              type="text" 
              placeholder="Contoh: A1, B2, Meja 1" 
              class="custom-input"
            >
          </div>
          
          <div class="form-group">
            <label>Kapasitas Kursi</label>
            <input 
              v-model="newTableCapacity" 
              type="number" 
              placeholder="0" 
              class="custom-input"
            >
          </div>

          <button class="primary-btn" @click="addTable">
            <ion-icon :icon="addOutline"></ion-icon>
            Simpan Meja
          </button>
        </div>

        <div class="section-divider">
          <span>Daftar Meja</span>
        </div>
        
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
           <ion-spinner name="crescent"></ion-spinner>
        </div>

        <!-- Table Grid -->
        <div v-else class="table-list">
           <div 
             v-for="table in tables" 
             :key="table.id" 
             class="table-item"
           >
             <div class="table-left">
               <div class="table-avatar">
                 {{ table.nomor_meja.substring(0,2).toUpperCase() }}
               </div>
               <div class="table-detail">
                 <h4>Meja {{ table.nomor_meja }}</h4>
                 <p>{{ table.kapasitas }} Kursi</p>
               </div>
             </div>
             
             <div class="table-right">
               <span class="status-pill" :class="table.status">
                 {{ table.status === 'booking' ? 'Terisi' : 'Tersedia' }}
               </span>
               <button class="delete-btn" @click="confirmDelete(table)">
                 <ion-icon :icon="trashOutline"></ion-icon>
               </button>
             </div>
           </div>
        </div>

      </div>
    </ion-content>

    <ion-toast :is-open="toast.show" :message="toast.msg" :color="toast.color" :duration="2000" @didDismiss="toast.show = false"></ion-toast>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent,
  IonIcon, IonSpinner, IonToast, alertController
} from '@ionic/vue'
import { addOutline, trashOutline } from 'ionicons/icons'
import { ref, onMounted, reactive } from 'vue'

const API_URL = 'http://localhost/restoo/Api_Mobile/mejas.php'

const tables = ref<any[]>([])
const loading = ref(false)
const newTableCode = ref('')
const newTableCapacity = ref('')
const toast = reactive({ show: false, msg: '', color: 'success' })

const showToast = (msg: string, color: string = 'success') => {
  toast.msg = msg
  toast.color = color
  toast.show = true
}

const loadTables = async () => {
  loading.value = true
  try {
    const res = await fetch(API_URL)
    const data = await res.json()
    if (data.status === 'success') {
      tables.value = data.data
    }
  } catch (e) {
    showToast('Gagal memuat data', 'danger')
  } finally {
    loading.value = false
  }
}

const addTable = async () => {
  if (!newTableCode.value || !newTableCapacity.value) {
    showToast('Harap isi semua kolom', 'warning')
    return
  }
  
  try {
    const res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        nomor_meja: newTableCode.value,
        kapasitas: newTableCapacity.value
      })
    })
    const data = await res.json()
    if (data.status === 'success') {
      showToast('Meja berhasil ditambahkan')
      newTableCode.value = ''
      newTableCapacity.value = ''
      loadTables()
    } else {
      showToast(data.msg, 'danger')
    }
  } catch (e) {
    showToast('Gagal menambah meja', 'danger')
  }
}

const confirmDelete = async (table: any) => {
  const alert = await alertController.create({
    header: 'Hapus Meja',
    message: `Yakin hapus meja ${table.nomor_meja}?`,
    buttons: [
      { text: 'Batal', role: 'cancel' },
      { 
        text: 'Hapus', 
        role: 'destructive',
        handler: () => deleteTable(table.id)
      }
    ]
  })
  await alert.present()
}

const deleteTable = async (id: number) => {
  try {
    const res = await fetch(API_URL, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    })
    const data = await res.json()
    if (data.status === 'success') {
      showToast('Meja dihapus')
      loadTables()
    } else {
      showToast('Gagal hapus', 'danger')
    }
  } catch (e) {
    showToast('Error network', 'danger')
  }
}

onMounted(loadTables)
</script>

<style scoped>
.main-content {
  --background: #f5f7fa;
}

.custom-toolbar {
  --background: white;
  --border-width: 0;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.page-title {
  font-weight: 700;
  color: #1a1a2e;
}

.content-wrapper {
  padding: 24px;
}

/* Form Card */
.form-card {
  background: white;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  margin-bottom: 30px;
}

.card-title {
  margin: 0 0 20px;
  font-size: 18px;
  font-weight: 700;
  color: #2d3748;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #718096;
  margin-bottom: 8px;
}

.custom-input {
  width: 100%;
  height: 48px;
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0 16px;
  font-size: 15px;
  color: #2d3748; /* Text visible */
  outline: none;
  transition: all 0.2s;
}

.custom-input:focus {
  background: white;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.primary-btn {
  width: 100%;
  height: 48px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  margin-top: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.primary-btn:active {
  transform: scale(0.98);
}

/* Section Divider */
.section-divider {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.section-divider span {
  font-size: 14px;
  font-weight: 700;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e2e8f0;
  margin-left: 12px;
}

/* List */
.table-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.table-item {
  background: white;
  padding: 16px;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

.table-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.table-avatar {
  width: 44px;
  height: 44px;
  background: #ebf4ff;
  border-radius: 12px;
  color: #5a67d8;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.table-detail h4 {
  margin: 0 0 4px;
  font-size: 15px;
  font-weight: 700;
  color: #2d3748;
}

.table-detail p {
  margin: 0;
  font-size: 13px;
  color: #718096;
}

.table-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-pill {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 6px;
  text-transform: uppercase;
}

.status-pill.available {
  background: #c6f6d5;
  color: #276749;
}

.status-pill.booking {
  background: #fed7d7;
  color: #9b2c2c;
}

.delete-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #fff5f5;
  color: #e53e3e;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  cursor: pointer;
  transition: background 0.2s;
}

.delete-btn:hover {
  background: #fee2e2;
}

.loading-state {
  text-align: center;
  padding: 20px;
}
</style>
