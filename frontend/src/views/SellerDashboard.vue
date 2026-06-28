<template>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <div>
        <h2>Dashboard Penjual</h2>
        <p>Kelola barang lelang Anda di sini</p>
      </div>
      <button @click="openCreateModal" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Tambah Lelang Baru
      </button>
    </div>

    <!-- Stats summary -->
    <div class="stats-grid">
      <div class="glass-card stat-card">
        <span class="stat-label">Total Lelang</span>
        <span class="stat-value">{{ auctions.length }}</span>
      </div>
      <div class="glass-card stat-card">
        <span class="stat-label">Lelang Aktif</span>
        <span class="stat-value text-success">{{ activeCount }}</span>
      </div>
      <div class="glass-card stat-card">
        <span class="stat-label">Lelang Terjadwal</span>
        <span class="stat-value text-warning">{{ scheduledCount }}</span>
      </div>
    </div>

    <!-- Auctions Table -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Memuat data lelang Anda...</p>
    </div>

    <div v-else-if="auctions.length === 0" class="empty-state glass-card">
      <h3>Belum ada lelang yang dibuat</h3>
      <p>Mulai dengan menambahkan lelang baru menggunakan tombol di atas.</p>
    </div>

    <div v-else class="table-wrapper glass">
      <table class="dashboard-table">
        <thead>
          <tr>
            <th>Barang</th>
            <th>Harga Awal</th>
            <th>Harga Saat Ini</th>
            <th>Waktu Mulai</th>
            <th>Waktu Selesai</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="auction in auctions" :key="auction.id">
            <td class="td-item">
              <div class="item-info">
                <img 
                  :src="auction.image_url || 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=200&auto=format&fit=crop'" 
                  alt="Auction Image" 
                  class="table-img"
                />
                <div>
                  <span class="item-title">{{ auction.title }}</span>
                  <span class="item-desc">{{ truncate(auction.description, 40) }}</span>
                </div>
              </div>
            </td>
            <td>Rp {{ formatNumber(auction.start_price) }}</td>
            <td class="text-success font-bold">
              Rp {{ formatNumber(auction.current_price) }}
            </td>
            <td>{{ formatDate(auction.starts_at) }}</td>
            <td>{{ formatDate(auction.ends_at) }}</td>
            <td>
              <span class="badge" :class="`badge-${auction.status}`">
                {{ formatStatus(auction.status) }}
              </span>
            </td>
            <td>
              <div class="actions-cell">
                <router-link :to="`/auction/${auction.id}`" class="btn btn-icon btn-secondary" title="Lihat">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </router-link>
                
                <button 
                  @click="openEditModal(auction)" 
                  class="btn btn-icon btn-secondary" 
                  :disabled="auction.status !== 'scheduled'"
                  title="Ubah"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </button>
                
                <button 
                  @click="deleteAuction(auction.id)" 
                  class="btn btn-icon btn-danger" 
                  :disabled="auction.status !== 'scheduled'"
                  title="Hapus"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="modal-overlay">
      <div class="glass-card modal-content animate-zoom-in">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Ubah Barang Lelang' : 'Tambah Lelang Baru' }}</h3>
          <button @click="closeModal" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="saveAuction" enctype="multipart/form-data">
          <div class="form-group">
            <label class="form-label" for="title">Nama Barang</label>
            <input type="text" id="title" v-model="form.title" class="form-control" required placeholder="Contoh: Sepatu Vintage Limited Edition" />
          </div>

          <div class="form-group">
            <label class="form-label" for="description">Deskripsi</label>
            <textarea id="description" v-model="form.description" class="form-control" rows="3" placeholder="Tuliskan deskripsi lengkap barang lelang Anda..."></textarea>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="start_price">Harga Awal (Rp)</label>
              <input type="number" id="start_price" v-model="form.start_price" class="form-control" required min="0" placeholder="100000" />
            </div>
            <div class="form-group">
              <label class="form-label" for="bid_increment">Kelipatan Kenaikan (Rp)</label>
              <input type="number" id="bid_increment" v-model="form.bid_increment" class="form-control" required min="1000" placeholder="10000" />
            </div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label" for="starts_at">Waktu Mulai</label>
              <input type="datetime-local" id="starts_at" v-model="form.starts_at" class="form-control" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="ends_at">Waktu Selesai</label>
              <input type="datetime-local" id="ends_at" v-model="form.ends_at" class="form-control" required />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="image">Foto Barang</label>
            <input type="file" id="image" @change="handleImageChange" class="form-control" accept="image/*" />
            <span class="file-help">Ukuran maks. 10MB. Format: JPG, PNG, WEBP</span>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="formLoading">
              <span v-if="formLoading" class="spinner-sm"></span>
              {{ formLoading ? 'Menyimpan...' : 'Simpan Lelang' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../utils/api';
import { useNotificationStore } from '../stores/notification';

const notificationStore = useNotificationStore();

const auctions = ref([]);
const loading = ref(true);
const modalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const formLoading = ref(false);

const form = ref({
  title: '',
  description: '',
  start_price: '',
  bid_increment: '',
  starts_at: '',
  ends_at: '',
  image: null,
});

// Stats computed properties
const activeCount = computed(() => auctions.value.filter(a => a.status === 'active').length);
const scheduledCount = computed(() => auctions.value.filter(a => a.status === 'scheduled').length);

const fetchAuctions = async () => {
  loading.value = true;
  try {
    const response = await api.get('/seller/auctions');
    auctions.value = response.data.auctions;
  } catch (error) {
    console.error('Error fetching seller auctions:', error);
    notificationStore.addToast('Gagal memuat data lelang Anda.', 'error');
  } finally {
    loading.value = false;
  }
};

const formatNumber = (num) => {
  return Number(num).toLocaleString('id-ID');
};

const truncate = (text, length) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const options = { 
    day: '2-digit', 
    month: 'short', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

const formatStatus = (status) => {
  if (status === 'active') return 'Aktif';
  if (status === 'scheduled') return 'Terjadwal';
  return 'Selesai';
};

const handleImageChange = (e) => {
  form.value.image = e.target.files[0] || null;
};

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.value = {
    title: '',
    description: '',
    start_price: '',
    bid_increment: '',
    starts_at: '',
    ends_at: '',
    image: null,
  };
  modalOpen.value = true;
};

const openEditModal = (auction) => {
  isEditing.value = true;
  editingId.value = auction.id;
  
  // Format dates for input type="datetime-local" (YYYY-MM-DDTHH:MM)
  const formatInputDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  };

  form.value = {
    title: auction.title,
    description: auction.description || '',
    start_price: Math.round(auction.start_price),
    bid_increment: Math.round(auction.bid_increment),
    starts_at: formatInputDate(auction.starts_at),
    ends_at: formatInputDate(auction.ends_at),
    image: null,
  };
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
};

const saveAuction = async () => {
  formLoading.value = true;
  
  // Create Form Data to support image uploads
  const formData = new FormData();
  formData.append('title', form.value.title);
  formData.append('description', form.value.description);
  formData.append('start_price', form.value.start_price);
  formData.append('bid_increment', form.value.bid_increment);
  
  // Parse input dates to full ISO strings or MySQL timestamps
  const startsAt = new Date(form.value.starts_at).toISOString();
  const endsAt = new Date(form.value.ends_at).toISOString();
  
  formData.append('starts_at', startsAt);
  formData.append('ends_at', endsAt);
  
  if (form.value.image) {
    formData.append('image', form.value.image);
  }

  try {
    if (isEditing.value) {
      // PHP/Laravel has issues parsing multipart/form-data on PUT/PATCH requests directly.
      // So we use POST and specify it is a POST call, but we have defined a POST fallback route for updates!
      await api.post(`/seller/auctions/${editingId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      notificationStore.addToast('Lelang berhasil diperbarui.', 'success');
    } else {
      await api.post('/seller/auctions', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      notificationStore.addToast('Lelang baru berhasil didaftarkan.', 'success');
    }
    
    closeModal();
    fetchAuctions();
  } catch (error) {
    console.error('Save error:', error);
    const msg = error.response?.data?.message || 'Gagal menyimpan lelang. Pastikan semua field valid.';
    notificationStore.addToast(msg, 'error');
  } finally {
    formLoading.value = false;
  }
};

const deleteAuction = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus lelang ini? Tindakan ini tidak dapat dibatalkan.')) {
    return;
  }
  
  try {
    await api.delete(`/seller/auctions/${id}`);
    notificationStore.addToast('Lelang berhasil dihapus.', 'success');
    fetchAuctions();
  } catch (error) {
    console.error('Delete error:', error);
    notificationStore.addToast(error.response?.data?.message || 'Gagal menghapus lelang.', 'error');
  }
};

onMounted(() => {
  fetchAuctions();
});
</script>

<style scoped>
.dashboard-container {
  padding: 40px 0;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.dashboard-header h2 {
  font-size: 2rem;
  font-weight: 800;
  background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.dashboard-header p {
  color: var(--text-secondary);
}

/* Stats Summary */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 40px;
}

@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

.stat-card {
  display: flex;
  flex-direction: column;
  padding: 20px;
}

.stat-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 2.2rem;
  font-weight: 800;
}

.text-success { color: #34d399; }
.text-warning { color: #fbbf24; }

/* Table styles */
.table-wrapper {
  overflow-x: auto;
  border-radius: 16px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
  margin-bottom: 40px;
}

.dashboard-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.dashboard-table th {
  background-color: rgba(15, 23, 42, 0.6);
  padding: 16px 20px;
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 0.9rem;
  border-bottom: 1px solid var(--border-color);
}

.dashboard-table td {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-color);
  font-size: 0.95rem;
  vertical-align: middle;
}

.dashboard-table tr:last-child td {
  border-bottom: none;
}

.dashboard-table tr:hover td {
  background-color: rgba(255, 255, 255, 0.02);
}

.td-item {
  min-width: 250px;
}

.item-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.table-img {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  object-fit: cover;
  background-color: #1e293b;
}

.item-title {
  display: block;
  font-weight: 600;
  color: var(--text-primary);
}

.item-desc {
  display: block;
  font-size: 0.75rem;
  color: var(--text-muted);
}

.actions-cell {
  display: flex;
  gap: 8px;
}

.btn-icon {
  padding: 8px;
  border-radius: 8px;
}

.btn-icon:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.font-bold {
  font-weight: 700;
}

/* Modal layout */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(8px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.modal-content {
  width: 100%;
  max-width: 600px;
  padding: 30px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.modal-header h3 {
  font-size: 1.4rem;
  font-weight: 700;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.8rem;
  cursor: pointer;
  color: var(--text-secondary);
}

.btn-close:hover {
  color: var(--text-primary);
}

.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

@media (max-width: 480px) {
  .grid-2 {
    grid-template-columns: 1fr;
    gap: 0;
  }
}

.file-help {
  display: block;
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 4px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 30px;
}

.spinner-sm {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 1s linear infinite;
  margin-right: 6px;
}

/* Loading and Empty state */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60px 0;
  gap: 12px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(99, 102, 241, 0.1);
  border-radius: 50%;
  border-top-color: var(--accent-primary);
  animation: spin 1s linear infinite;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: var(--text-secondary);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes zoomIn {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.animate-zoom-in {
  animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
