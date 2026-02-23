<template>
  <ion-page>
    <ion-header :translucent="true" class="ion-no-border">
      <ion-toolbar class="custom-toolbar">
        <ion-buttons slot="start">
          <ion-back-button default-href="/home" color="primary" text=""></ion-back-button>
        </ion-buttons>
        <ion-title class="toolbar-title">Manajemen Menu</ion-title>
        <ion-buttons slot="end">
          <ion-button class="add-btn" @click="openAddModal">
            <ion-icon :icon="addCircleOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="menu-content">
      <!-- Search Bar -->
      <div class="search-container">
        <div class="search-wrapper">
          <ion-icon :icon="searchOutline" class="search-icon"></ion-icon>
          <ion-input 
            v-model="searchQuery" 
            placeholder="Cari menu..." 
            class="search-input"
          ></ion-input>
          <ion-icon 
            v-if="searchQuery" 
            :icon="closeCircleOutline" 
            class="clear-icon"
            @click="searchQuery = ''"
          ></ion-icon>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="state-container">
        <div class="loading-animation">
          <ion-spinner name="crescent" color="primary"></ion-spinner>
        </div>
        <p class="state-text">Memuat data menu...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredMenus.length === 0 && !searchQuery" class="state-container">
        <div class="empty-illustration">
          <ion-icon :icon="restaurantOutline"></ion-icon>
        </div>
        <h2 class="state-title">Belum Ada Menu</h2>
        <p class="state-text">Mulai tambahkan menu restoran Anda</p>
        <ion-button class="add-first-btn" @click="openAddModal">
          <ion-icon :icon="addOutline" slot="start"></ion-icon>
          Tambah Menu Pertama
        </ion-button>
      </div>

      <!-- No Search Results -->
      <div v-else-if="filteredMenus.length === 0 && searchQuery" class="state-container">
        <div class="empty-illustration">
          <ion-icon :icon="searchOutline"></ion-icon>
        </div>
        <h2 class="state-title">Tidak Ditemukan</h2>
        <p class="state-text">Menu "{{ searchQuery }}" tidak ditemukan</p>
      </div>

      <!-- Menu Grid -->
      <div v-else class="menu-grid">
        <div 
          v-for="menu in filteredMenus" 
          :key="menu.id" 
          class="menu-card"
          :class="{ 'sold-out': menu.status === 'sold_out' }"
        >
          <!-- Card Header & Image -->
          <div class="card-header">
            <div class="menu-image-container">
              <img v-if="menu.foto" :src="'http://localhost/restoo/Api_Mobile/uploads/' + menu.foto" class="card-img" />
              <div v-else class="menu-icon-wrapper" :class="menu.status === 'available' ? 'available' : 'unavailable'">
                <ion-icon :icon="fastFoodOutline"></ion-icon>
              </div>
            </div>
            <span class="status-badge" :class="menu.status === 'available' ? 'badge-success' : 'badge-danger'">
              {{ menu.status === 'available' ? 'TERSEDIA' : 'HABIS' }}
            </span>
          </div>

          <!-- Card Body -->
          <div class="card-body">
            <h3 class="menu-name">{{ menu.nama_menu }}</h3>
            <p class="menu-price">Rp {{ formatPrice(menu.harga) }}</p>
            <div class="stock-info">
              <ion-icon :icon="cubeOutline"></ion-icon>
              <span>Stok: {{ menu.stok_porsi }}</span>
            </div>
          </div>

          <!-- Card Actions -->
          <div class="card-actions">
            <ion-button fill="clear" class="action-btn edit" @click="openEditModal(menu)">
              <ion-icon :icon="createOutline" slot="icon-only"></ion-icon>
            </ion-button>
            <ion-button fill="clear" class="action-btn delete" @click="confirmDelete(menu)">
              <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
            </ion-button>
          </div>
        </div>
      </div>

      <!-- FAB Refresh -->
      <ion-fab vertical="bottom" horizontal="end" slot="fixed">
        <ion-fab-button @click="loadMenus" class="refresh-fab">
          <ion-icon :icon="refreshOutline"></ion-icon>
        </ion-fab-button>
      </ion-fab>
    </ion-content>

    <!-- Add/Edit Modal -->
    <ion-modal :is-open="isModalOpen" @didDismiss="closeModal" class="custom-modal">
      <ion-header class="ion-no-border">
        <ion-toolbar class="modal-toolbar">
          <ion-title class="modal-title">{{ isEditing ? 'Edit Menu' : 'Tambah Menu' }}</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="closeModal" class="close-btn">
              <ion-icon :icon="closeOutline"></ion-icon>
            </ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>

      <ion-content class="modal-content">
        <div class="form-container">
          <!-- Image Upload -->
          <div class="form-group">
            <label class="form-label">Foto Menu</label>
            <div class="image-upload-wrapper" @click="$refs.fileInput.click()">
              <input 
                type="file" 
                ref="fileInput" 
                style="display: none" 
                accept="image/*"
                @change="onFileChange"
              />
              <div v-if="formData.imagePreview" class="preview-container">
                <img :src="formData.imagePreview" class="image-preview" />
                <div class="change-overlay">Ubah Foto</div>
              </div>
              <div v-else class="upload-placeholder">
                <ion-icon :icon="cameraOutline"></ion-icon>
                <span>Ketuk untuk pilih foto</span>
              </div>
            </div>
          </div>

          <!-- Menu Name -->
          <div class="form-group">
            <label class="form-label">Nama Menu</label>
            <div class="input-wrapper">
              <ion-icon :icon="restaurantOutline" class="input-icon"></ion-icon>
              <ion-input 
                v-model="formData.nama_menu" 
                placeholder="Masukkan nama menu" 
                class="form-input"
              ></ion-input>
            </div>
          </div>

          <!-- Price -->
          <div class="form-group">
            <label class="form-label">Harga</label>
            <div class="input-wrapper">
              <span class="currency-prefix">Rp</span>
              <ion-input 
                v-model.number="formData.harga" 
                placeholder="0" 
                type="number"
                class="form-input price-input"
              ></ion-input>
            </div>
          </div>

          <!-- Stock -->
          <div class="form-group">
            <label class="form-label">Stok</label>
            <div class="input-wrapper">
              <ion-icon :icon="cubeOutline" class="input-icon"></ion-icon>
              <ion-input 
                v-model.number="formData.stok_porsi" 
                placeholder="0" 
                type="number"
                class="form-input"
              ></ion-input>
            </div>
          </div>

          <!-- Status -->
          <div class="form-group">
            <label class="form-label">Status</label>
            <div class="status-toggle">
              <div 
                class="toggle-option" 
                :class="{ active: formData.status === 'available' }"
                @click="formData.status = 'available'"
              >
                <ion-icon :icon="checkmarkCircleOutline"></ion-icon>
                <span>Tersedia</span>
              </div>
              <div 
                class="toggle-option" 
                :class="{ active: formData.status === 'sold_out' }"
                @click="formData.status = 'sold_out'"
              >
                <ion-icon :icon="closeCircleOutline"></ion-icon>
                <span>Sold Out</span>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <ion-button 
            expand="block" 
            class="submit-btn" 
            @click="saveMenu" 
            :disabled="saving"
          >
            <ion-spinner v-if="saving" name="crescent"></ion-spinner>
            <span v-else>{{ isEditing ? 'Update Menu' : 'Simpan Menu' }}</span>
          </ion-button>
        </div>
      </ion-content>
    </ion-modal>

    <!-- Delete Confirmation Alert -->
    <ion-alert
      :is-open="isDeleteAlertOpen"
      header="Hapus Menu"
      :message="`Apakah Anda yakin ingin menghapus '${menuToDelete?.nama_menu}'?`"
      :buttons="deleteAlertButtons"
      @didDismiss="isDeleteAlertOpen = false"
    ></ion-alert>

    <!-- Toast -->
    <ion-toast
      :is-open="isToastOpen"
      :message="toastMessage"
      :duration="3000"
      :color="toastColor"
      position="top"
      @didDismiss="isToastOpen = false"
    ></ion-toast>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButton,
  IonButtons,
  IonIcon,
  IonModal,
  IonInput,
  IonSpinner,
  IonFab,
  IonFabButton,
  IonBackButton,
  IonAlert,
  IonToast
} from '@ionic/vue'

import {
  addOutline,
  addCircleOutline,
  createOutline,
  trashOutline,
  closeOutline,
  refreshOutline,
  restaurantOutline,
  fastFoodOutline,
  searchOutline,
  cameraOutline,
  closeCircleOutline,
  checkmarkCircleOutline,
  cubeOutline
} from 'ionicons/icons'

import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const API_URL = 'http://localhost/restoo/Api_Mobile/menus.php'

const router = useRouter()

// State
const menus = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const isModalOpen = ref(false)
const isEditing = ref(false)
const isDeleteAlertOpen = ref(false)
const isToastOpen = ref(false)
const toastMessage = ref('')
const toastColor = ref('success')
const menuToDelete = ref<any>(null)
const searchQuery = ref('')

// Form data
const formData = reactive({
  id: null as number | null,
  nama_menu: '',
  harga: 0,
  stok_porsi: 0,
  status: 'available',
  fotoFile: null as File | null,
  imagePreview: '' as string
})

// Computed - filtered menus
const filteredMenus = computed(() => {
  if (!searchQuery.value) return menus.value
  const query = searchQuery.value.toLowerCase()
  return menus.value.filter(menu => 
    menu.nama_menu.toLowerCase().includes(query)
  )
})

// Delete alert buttons
const deleteAlertButtons = [
  {
    text: 'Batal',
    role: 'cancel'
  },
  {
    text: 'Hapus',
    role: 'destructive',
    handler: () => {
      deleteMenu()
    }
  }
]

// Check auth
onMounted(() => {
  const user = localStorage.getItem('user')
  if (!user) {
    router.replace('/login')
    return
  }
  loadMenus()
})

// Format price
const formatPrice = (price: number) => {
  return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

// Show toast
const showToast = (message: string, color: string = 'success') => {
  toastMessage.value = message
  toastColor.value = color
  isToastOpen.value = true
}

// Load all menus
const loadMenus = async () => {
  loading.value = true
  try {
    const response = await fetch(API_URL)
    const result = await response.json()
    
    if (result.status === 'success') {
      menus.value = result.data
    } else {
      showToast(result.msg || 'Gagal memuat menu', 'danger')
    }
  } catch (error) {
    console.error('Error loading menus:', error)
    showToast('Terjadi kesalahan saat memuat data', 'danger')
  } finally {
    loading.value = false
  }
}

// Open add modal
const openAddModal = () => {
  isEditing.value = false
  formData.id = null
  formData.nama_menu = ''
  formData.harga = 0
  formData.stok_porsi = 0
  formData.status = 'available'
  formData.fotoFile = null
  formData.imagePreview = ''
  isModalOpen.value = true
}

// Open edit modal
const openEditModal = (menu: any) => {
  isEditing.value = true
  formData.id = menu.id
  formData.nama_menu = menu.nama_menu
  formData.harga = parseInt(menu.harga)
  formData.stok_porsi = parseInt(menu.stok_porsi)
  formData.status = menu.status
  formData.fotoFile = null
  formData.imagePreview = menu.foto ? `http://localhost/restoo/Api_Mobile/uploads/${menu.foto}` : ''
  isModalOpen.value = true
}

// Close modal
const closeModal = () => {
  isModalOpen.value = false
}

// Validate form
const validateForm = () => {
  if (!formData.nama_menu.trim()) {
    showToast('Nama menu harus diisi', 'warning')
    return false
  }
  if (formData.harga <= 0) {
    showToast('Harga harus lebih dari 0', 'warning')
    return false
  }
  if (formData.stok_porsi < 0) {
    showToast('Stok tidak boleh negatif', 'warning')
    return false
  }
  return true
}

// Handle file change
const onFileChange = (e: any) => {
  const file = e.target.files[0]
  if (!file) return
  
  formData.fotoFile = file
  formData.imagePreview = URL.createObjectURL(file)
}

// Save menu
const saveMenu = async () => {
  if (!validateForm()) return
  
  saving.value = true
  try {
    const fd = new FormData()
    fd.append('nama_menu', formData.nama_menu)
    fd.append('harga', formData.harga.toString())
    fd.append('stok_porsi', formData.stok_porsi.toString())
    fd.append('status', formData.status)
    if (formData.fotoFile) {
      fd.append('foto', formData.fotoFile)
    }
    
    if (isEditing.value && formData.id) {
      fd.append('id', formData.id.toString())
    }

    const response = await fetch(API_URL, {
      method: 'POST', // Use POST for both Insert and Update when using FormData
      body: fd
    })
    
    const result = await response.json()
    
    if (result.status === 'success') {
      showToast(result.msg || (isEditing.value ? 'Menu berhasil diupdate' : 'Menu berhasil ditambahkan'))
      closeModal()
      loadMenus()
    } else {
      showToast(result.msg || 'Gagal menyimpan menu', 'danger')
    }
  } catch (error) {
    console.error('Error saving menu:', error)
    showToast('Terjadi kesalahan saat menyimpan', 'danger')
  } finally {
    saving.value = false
  }
}

// Confirm delete
const confirmDelete = (menu: any) => {
  menuToDelete.value = menu
  isDeleteAlertOpen.value = true
}

// Delete menu
const deleteMenu = async () => {
  if (!menuToDelete.value) return
  
  try {
    const response = await fetch(API_URL, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ id: menuToDelete.value.id })
    })
    
    const result = await response.json()
    
    if (result.status === 'success') {
      showToast('Menu berhasil dihapus')
      loadMenus()
    } else {
      showToast(result.msg || 'Gagal menghapus menu', 'danger')
    }
  } catch (error) {
    console.error('Error deleting menu:', error)
    showToast('Terjadi kesalahan saat menghapus', 'danger')
  } finally {
    menuToDelete.value = null
  }
}
</script>

<style scoped>
.menu-content {
  --background: #f5f7fb;
}

.custom-toolbar {
  --background: #f5f7fb;
  --border-width: 0;
  padding: 8px 4px 0;
}

.toolbar-title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
}

.add-btn {
  --color: #667eea;
}

.add-btn ion-icon {
  font-size: 28px;
}

/* Search */
.search-container {
  padding: 8px 16px 16px;
}

.search-wrapper {
  display: flex;
  align-items: center;
  background: white;
  border-radius: 14px;
  padding: 0 16px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.search-icon {
  font-size: 20px;
  color: #adb5bd;
}

.search-input {
  --padding-start: 12px;
  --padding-end: 8px;
  --padding-top: 14px;
  --padding-bottom: 14px;
  font-size: 15px;
  --color: #1a1a2e;
  --placeholder-color: #adb5bd;
}

/* Image Upload */
.image-upload-wrapper {
  width: 100%;
  height: 160px;
  background: white;
  border-radius: 18px;
  border: 2px dashed #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;
}

.image-upload-wrapper:hover {
  border-color: #667eea;
  background: #f8faff;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  color: #adb5bd;
}

.upload-placeholder ion-icon {
  font-size: 40px;
}

.upload-placeholder span {
  font-size: 13px;
  font-weight: 500;
}

.preview-container {
  width: 100%;
  height: 100%;
  position: relative;
}

.image-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.change-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.5);
  color: white;
  padding: 8px;
  font-size: 12px;
  font-weight: 600;
  text-align: center;
  backdrop-filter: blur(4px);
}

.clear-icon {
  font-size: 20px;
  color: #adb5bd;
  cursor: pointer;
}

/* States */
.state-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  text-align: center;
}

.loading-animation ion-spinner {
  width: 50px;
  height: 50px;
}

.empty-illustration {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
}

.empty-illustration ion-icon {
  font-size: 50px;
  color: white;
}

.state-title {
  font-size: 20px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 8px;
}

.state-text {
  font-size: 14px;
  color: #6c757d;
  margin: 0;
}

.add-first-btn {
  margin-top: 24px;
  --background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --border-radius: 12px;
  --box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
  height: 48px;
  font-weight: 600;
}

/* Menu Grid */
.menu-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
  padding: 0 16px 100px;
}

.menu-card {
  background: white;
  border-radius: 18px;
  padding: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
}

.menu-image-container {
  width: 50px;
  height: 50px;
}

.card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 12px;
}

.menu-card.sold-out {
  opacity: 0.7;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.menu-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.menu-icon-wrapper.available {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.menu-icon-wrapper.unavailable {
  background: #e9ecef;
}

.menu-icon-wrapper ion-icon {
  font-size: 22px;
  color: white;
}

.menu-icon-wrapper.unavailable ion-icon {
  color: #6c757d;
}

.status-badge {
  font-size: 9px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-success {
  background: #d4edda;
  color: #28a745;
}

.badge-danger {
  background: #f8d7da;
  color: #dc3545;
}

.card-body {
  margin-bottom: 12px;
}

.menu-name {
  font-size: 14px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0 0 6px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.menu-price {
  font-size: 16px;
  font-weight: 700;
  color: #667eea;
  margin: 0 0 8px;
}

.stock-info {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #6c757d;
}

.stock-info ion-icon {
  font-size: 14px;
}

.card-actions {
  display: flex;
  gap: 8px;
  border-top: 1px solid #f0f0f0;
  padding-top: 12px;
}

.action-btn {
  flex: 1;
  --padding-start: 0;
  --padding-end: 0;
  height: 36px;
  margin: 0;
}

.action-btn.edit {
  --color: #667eea;
}

.action-btn.delete {
  --color: #dc3545;
}

.refresh-fab {
  --background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

/* Modal */
.modal-toolbar {
  --background: white;
  --border-width: 0;
  padding-top: 8px;
}

.modal-title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
}

.close-btn {
  --color: #6c757d;
}

.close-btn ion-icon {
  font-size: 26px;
}

.modal-content {
  --background: #f5f7fb;
}

.form-container {
  padding: 24px 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.input-wrapper {
  display: flex;
  align-items: center;
  background: white;
  border-radius: 14px;
  border: 2px solid transparent;
  padding: 0 16px;
  transition: all 0.3s;
}

.input-wrapper:focus-within {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.input-icon {
  font-size: 20px;
  color: #adb5bd;
}

.currency-prefix {
  font-size: 16px;
  font-weight: 600;
  color: #667eea;
}

.form-input {
  --padding-start: 12px;
  --padding-end: 12px;
  --padding-top: 16px;
  --padding-bottom: 16px;
  font-size: 15px;
  --color: #1a1a2e;
  --placeholder-color: #adb5bd;
}

.price-input {
  --padding-start: 8px;
}

.status-toggle {
  display: flex;
  gap: 12px;
}

.toggle-option {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px;
  background: white;
  border-radius: 14px;
  border: 2px solid #e9ecef;
  cursor: pointer;
  transition: all 0.3s;
}

.toggle-option.active {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.toggle-option ion-icon {
  font-size: 20px;
  color: #adb5bd;
}

.toggle-option.active ion-icon {
  color: #667eea;
}

.toggle-option span {
  font-size: 14px;
  font-weight: 500;
  color: #6c757d;
}

.toggle-option.active span {
  color: #667eea;
}

.submit-btn {
  margin-top: 16px;
  --background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --border-radius: 14px;
  --box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
  height: 52px;
  font-size: 16px;
  font-weight: 600;
}
</style>
