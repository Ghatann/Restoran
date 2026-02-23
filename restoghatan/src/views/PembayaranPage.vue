<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar class="custom-toolbar">
        <ion-buttons slot="start">
          <ion-back-button default-href="/home" color="primary" text=""></ion-back-button>
        </ion-buttons>
        <ion-title class="page-title">Pembayaran</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="main-content">
      <div class="content-wrapper">

        <!-- Step 1 : Pilih Meja Terisi -->
        <div v-if="!selectedTable">
          <div class="page-header">
            <h2>Pilih Meja</h2>
            <p>Pilih meja yang akan melakukan pembayaran</p>
          </div>

          <div v-if="loading" class="center-spin">
            <ion-spinner name="crescent" color="primary"></ion-spinner>
          </div>

          <div v-else-if="occupiedTables.length === 0" class="empty-state">
            <div class="empty-icon">
              <ion-icon :icon="checkmarkCircleOutline"></ion-icon>
            </div>
            <h3>Semua Meja Bersih</h3>
            <p>Tidak ada tagihan yang belum dibayar saat ini.</p>
          </div>

          <div v-else class="table-list">
            <div
              v-for="table in occupiedTables"
              :key="table.id"
              class="bill-card"
              @click="loadBill(table)"
            >
              <div class="bill-info">
                <div class="bill-icon">
                  <ion-icon :icon="receiptOutline"></ion-icon>
                </div>
                <div class="bill-text">
                  <h3>{{ table.customer_name || 'Pelanggan' }} (Meja {{ table.nomor_meja }})</h3>
                  <span class="status-tag">Belum Bayar</span>
                </div>
              </div>
              <div class="bill-action">
                <span>Proses</span>
                <ion-icon :icon="chevronForward"></ion-icon>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2 : Invoice & Bayar -->
        <div v-else class="invoice-container fade-in">
          <div class="invoice-card">
            <div class="invoice-header">
              <div class="header-row">
                <span class="label">Tagihan Meja</span>
                <span class="table-number">{{ selectedTable.customer_name || 'Pelanggan' }} ({{ selectedTable.nomor_meja }})</span>
              </div>
              <div class="date-row">{{ getCurrentDate() }}</div>
            </div>

            <div class="divider"></div>

            <!-- Loading items -->
            <div v-if="loadingBill" class="center-spin">
              <ion-spinner name="crescent" color="primary"></ion-spinner>
            </div>

            <!-- Items -->
            <div v-else class="order-items">
              <div v-if="billItems.length === 0" class="no-items">
                Tidak ada pesanan aktif untuk meja ini.
              </div>
              <template v-for="order in billOrders" :key="order.id">
                <div v-for="item in order.items" :key="item.id" class="order-row">
                  <div class="item-visual">
                    <img v-if="item.foto" :src="'http://localhost/restoo/Api_Mobile/uploads/' + item.foto" class="item-thumb" />
                    <div v-else class="item-thumb-placeholder">
                      <ion-icon :icon="cardOutline"></ion-icon>
                    </div>
                  </div>
                  <div class="item-desc">
                    <span class="item-qty">{{ item.jumlah }}x</span>
                    <span class="item-name">{{ item.nama_menu }}</span>
                  </div>
                  <span class="item-total">Rp {{ formatPrice(item.subtotal) }}</span>
                </div>
              </template>
            </div>

            <div class="divider dashed"></div>

            <!-- Metode Pembayaran -->
            <div class="payment-method-section">
              <label class="section-label">Metode Pembayaran</label>
              <div class="method-options">
                <div
                  class="method-card"
                  :class="{ active: paymentMethod === 'cash' }"
                  @click="paymentMethod = 'cash'"
                >
                  <ion-icon :icon="cashOutline"></ion-icon>
                  <span>Tunai (Cash)</span>
                </div>
                <div
                  class="method-card"
                  :class="{ active: paymentMethod === 'qris' }"
                  @click="paymentMethod = 'qris'"
                >
                  <ion-icon :icon="qrCodeOutline"></ion-icon>
                  <span>E-Wallet (QRIS)</span>
                </div>
              </div>
            </div>

            <!-- QRIS Code -->
            <div v-if="paymentMethod === 'qris'" class="qris-container fade-in">
              <div class="qris-box">
                <img
                  src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg"
                  alt="QRIS Code"
                  class="qris-img"
                />
                <p>Scan untuk membayar</p>
              </div>
            </div>

            <div class="divider dashed"></div>

            <div class="invoice-total">
              <span>Total Tagihan</span>
              <span class="amount">Rp {{ formatPrice(grandTotal) }}</span>
            </div>
          </div>

          <div class="action-buttons">
            <button class="primary-btn pay-btn" @click="processPayment" :disabled="processing || loadingBill || grandTotal === 0">
              <ion-spinner v-if="processing" name="crescent"></ion-spinner>
              <div v-else class="btn-content">
                <ion-icon :icon="cashOutline"></ion-icon>
                <span>Bayar — Rp {{ formatPrice(grandTotal) }}</span>
              </div>
            </button>
            <button class="secondary-btn" @click="selectedTable = null" :disabled="processing">
              Kembali
            </button>
          </div>
        </div>

      </div>
    </ion-content>

    <ion-toast
      :is-open="toast.show"
      :message="toast.msg"
      :color="toast.color"
      :duration="2500"
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
  chevronForward, receiptOutline, cashOutline, checkmarkCircleOutline,
  qrCodeOutline, cardOutline
} from 'ionicons/icons'
import { ref, reactive, computed, onMounted } from 'vue'

const API_MEJA  = 'http://localhost/restoo/Api_Mobile/mejas.php'
const API_ORDER = 'http://localhost/restoo/Api_Mobile/orderans.php'

// State
const occupiedTables = ref<any[]>([])
const selectedTable  = ref<any>(null)
const billOrders     = ref<any[]>([])
const loading        = ref(false)
const loadingBill    = ref(false)
const processing     = ref(false)
const paymentMethod  = ref('cash')
const toast          = reactive({ show: false, msg: '', color: 'success' })

// All detail items flattened (for convenience)
const billItems = computed(() => billOrders.value.flatMap((o: any) => o.items || []))

// Grand total from items' subtotals
const grandTotal = computed(() =>
  billItems.value.reduce((sum: number, i: any) => sum + Number(i.subtotal), 0)
)

const formatPrice = (p: number) =>
  Math.round(p).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')

const getCurrentDate = () =>
  new Date().toLocaleDateString('id-ID', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })

const showToast = (msg: string, color = 'success') => {
  toast.msg   = msg
  toast.color = color
  toast.show  = true
}

// Load all tables that are 'booking'
const loadOccupiedTables = async () => {
  loading.value = true
  try {
    const [resT, resO] = await Promise.all([
      fetch(API_MEJA).then(r => r.json()),
      fetch(API_ORDER).then(r => r.json())
    ])

    if (resT.status === 'success' && resO.status === 'success') {
      const pendingOrders = resO.data.filter((o: any) => o.status === 'pending')
      occupiedTables.value = resT.data
        .filter((t: any) => t.status === 'booking')
        .map((t: any) => {
          const order = pendingOrders.find((o: any) => o.id_meja === t.id)
          return {
            ...t,
            customer_name: order ? order.nama_konsumen : 'Pelanggan'
          }
        })
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Load pending orders for a table (includes items)
const loadBill = async (table: any) => {
  selectedTable.value = table
  paymentMethod.value = 'cash'
  loadingBill.value   = true
  billOrders.value    = []

  try {
    const res  = await fetch(`${API_ORDER}?id_meja=${table.id}`)
    const data = await res.json()
    if (data.status === 'success') {
      billOrders.value = data.data
    } else {
      showToast(data.msg || 'Gagal memuat tagihan', 'danger')
    }
  } catch (e) {
    showToast('Terjadi kesalahan saat memuat tagihan', 'danger')
  } finally {
    loadingBill.value = false
  }
}

// Mark all pending orders for this table as 'dibayar'
const processPayment = async () => {
  if (!selectedTable.value || billOrders.value.length === 0) return
  processing.value = true

  try {
    // Update each pending order to 'dibayar' with chosen payment method
    for (const order of billOrders.value) {
      await fetch(API_ORDER, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id:     order.id,
          status: 'dibayar',
          metode_pembayaran: paymentMethod.value,
        }),
      })
    }

    // The PUT → dibayar handler in orderans.php already frees the table (sets booking→available)
    showToast('Pembayaran Berhasil! 🎉', 'success')
    setTimeout(() => {
      selectedTable.value = null
      billOrders.value    = []
      loadOccupiedTables()
    }, 800)
  } catch (e) {
    showToast('Gagal proses pembayaran', 'danger')
  } finally {
    processing.value = false
  }
}

onMounted(loadOccupiedTables)
</script>

<style scoped>
/* ── Layout ────────────────────────────────────────────────────── */
.main-content   { --background: #f5f7fa; }
.content-wrapper { padding: 24px; }

.custom-toolbar {
  --background: white;
  --border-width: 0;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.page-title { font-weight: 700; color: #1a1a2e; }

.center-spin {
  display: flex;
  justify-content: center;
  padding: 40px 0;
}

/* ── Page Header ──────────────────────────────────────────────── */
.page-header { margin-bottom: 24px; }
.page-header h2 { font-size: 24px; font-weight: 800; color: #1a1a2e; margin: 0 0 8px; }
.page-header p  { color: #718096; margin: 0; font-size: 15px; }

/* ── Empty ────────────────────────────────────────────────────── */
.empty-state { text-align: center; padding: 60px 20px; }
.empty-icon  {
  width: 80px; height: 80px;
  background: #e6fffa; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
}
.empty-icon ion-icon { font-size: 40px; color: #38b2ac; }
.empty-state h3 { font-weight: 700; color: #2d3748; margin-bottom: 8px; }
.empty-state p  { color: #a0aec0; }

/* ── Bill Cards ───────────────────────────────────────────────── */
.table-list { display: flex; flex-direction: column; gap: 16px; }

.bill-card {
  background: white; padding: 16px 20px;
  border-radius: 16px;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  cursor: pointer; transition: transform 0.2s;
}
.bill-card:active { transform: scale(0.98); }
.bill-info { display: flex; align-items: center; gap: 16px; }
.bill-icon {
  width: 48px; height: 48px;
  background: #ebf8ff; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
}
.bill-icon ion-icon { font-size: 24px; color: #4299e1; }
.bill-text h3 { margin: 0 0 4px; font-weight: 700; color: #2d3748; font-size: 16px; }
.status-tag {
  background: #fff5f5; color: #e53e3e;
  font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 4px;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.bill-action { display: flex; align-items: center; gap: 8px; color: #a0aec0; font-size: 13px; font-weight: 500; }

/* ── Invoice ──────────────────────────────────────────────────── */
.invoice-card {
  background: white; border-radius: 20px;
  padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);
  margin-bottom: 24px;
}
.invoice-header { margin-bottom: 20px; }
.header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.header-row .label { color: #718096; font-size: 14px; }
.header-row .table-number { font-size: 24px; font-weight: 800; color: #2d3748; }
.date-row { font-size: 13px; color: #a0aec0; }

.divider { height: 1px; background: #e2e8f0; margin: 16px 0; }
.divider.dashed { background: none; border-top: 2px dashed #e2e8f0; }

.order-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; font-size: 15px; }
.item-visual { flex-shrink: 0; }
.item-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }
.item-thumb-placeholder { 
  width: 40px; height: 40px; border-radius: 8px; background: #f7fafc;
  display: flex; align-items: center; justify-content: center; color: #cbd5e0;
}
.item-desc { flex: 1; color: #4a5568; }
.item-qty  { font-weight: 600; margin-right: 8px; color: #2d3748; }
.item-total { font-weight: 600; color: #2d3748; }

.no-items { color: #a0aec0; font-size: 14px; text-align: center; padding: 12px 0; }

.invoice-total { display: flex; justify-content: space-between; align-items: center; margin-top: 4px; }
.invoice-total span:first-child { color: #718096; font-weight: 500; }
.invoice-total .amount { font-size: 22px; font-weight: 800; color: #667eea; }

/* ── Payment Method ───────────────────────────────────────────── */
.payment-method-section { margin: 16px 0; }
.section-label { display: block; font-size: 13px; font-weight: 600; color: #718096; margin-bottom: 8px; }
.method-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.method-card {
  border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px;
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  cursor: pointer; transition: all 0.2s; color: #718096;
}
.method-card ion-icon { font-size: 22px; }
.method-card span { font-size: 12px; font-weight: 600; }
.method-card.active { border-color: #667eea; background: #ebf4ff; color: #5a67d8; }

/* ── QRIS ─────────────────────────────────────────────────────── */
.qris-container { display: flex; justify-content: center; margin: 20px 0; }
.qris-box { background: white; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; text-align: center; }
.qris-img { width: 180px; height: 180px; object-fit: contain; }
.qris-box p { margin: 8px 0 0; font-size: 13px; color: #718096; font-weight: 500; }

/* ── Buttons ──────────────────────────────────────────────────── */
.action-buttons { display: flex; flex-direction: column; gap: 12px; }

.primary-btn {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white; border: none; border-radius: 14px;
  height: 56px; font-size: 16px; font-weight: 700; width: 100%;
  cursor: pointer; box-shadow: 0 4px 15px rgba(72,187,120,0.4);
  transition: transform 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.primary-btn:active { transform: scale(0.98); }
.primary-btn:disabled { background: #cbd5e0; box-shadow: none; cursor: not-allowed; }
.btn-content { display: flex; align-items: center; gap: 10px; }

.secondary-btn {
  background: transparent; color: #718096;
  border: 1px solid #cbd5e0; border-radius: 14px;
  height: 48px; font-size: 15px; font-weight: 600; width: 100%; cursor: pointer;
}

/* ── Animations ───────────────────────────────────────────────── */
.fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
