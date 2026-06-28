<template>
  <div v-if="loading" class="loading-container">
    <div class="spinner-lg"></div>
    <p>Memuat detail lelang...</p>
  </div>

  <div v-else-if="!auction" class="empty-state glass-card">
    <h3>Lelang tidak ditemukan</h3>
    <p>Halaman lelang yang Anda cari tidak ada atau telah dihapus.</p>
    <router-link to="/" class="btn btn-primary">Kembali ke Beranda</router-link>
  </div>

  <div v-else class="detail-container">
    <!-- Winner announcement banner if ended -->
    <div v-if="auction.status === 'ended'" class="winner-banner animate-slide-in">
      <div class="banner-icon">🏆</div>
      <div v-if="winner" class="banner-content">
        <h3>Lelang Telah Berakhir!</h3>
        <p>Pemenang: <strong>{{ winner.name }}</strong> dengan penawaran <strong>Rp {{ formatNumber(winningBidAmount) }}</strong></p>
      </div>
      <div v-else class="banner-content">
        <h3>Lelang Telah Berakhir</h3>
        <p>Tidak ada penawaran yang masuk untuk lelang ini.</p>
      </div>
    </div>

    <div class="detail-grid">
      <!-- Left side: Image and description -->
      <div class="left-column">
        <div class="glass-card image-card">
          <img 
            :src="auction.image_url || 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=800&auto=format&fit=crop'" 
            alt="Auction Item Image" 
            class="detail-image"
          />
          <div class="image-overlay">
            <span class="badge" :class="`badge-${auction.status}`">
              {{ formatStatus(auction.status) }}
            </span>
            <div class="watcher-count-pill glass" v-if="auction.status !== 'ended'">
              <span class="pulse-dot"></span>
              <span>{{ watchersCount }} Menonton</span>
            </div>
          </div>
        </div>

        <div class="glass-card desc-card">
          <h3>Deskripsi Barang</h3>
          <p class="desc-text">{{ auction.description || 'Tidak ada deskripsi untuk barang ini.' }}</p>
          <div class="seller-info">
            <div class="avatar">{{ auction.seller?.name?.charAt(0).toUpperCase() || 'S' }}</div>
            <div>
              <span class="seller-label">Penjual</span>
              <span class="seller-name">{{ auction.seller?.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side: Bidding form and stats -->
      <div class="right-column">
        <!-- Auction Info Card -->
        <div class="glass-card info-card">
          <h2 class="item-title">{{ auction.title }}</h2>
          
          <div class="countdown-section">
            <AuctionCountdown 
              :starts-at="auction.starts_at" 
              :ends-at="auction.ends_at" 
              :status="auction.status"
              @countdown-ended="handleCountdownEnded"
            />
          </div>

          <div class="price-section">
            <div class="price-box">
              <span class="box-lbl">Harga Tertinggi Saat Ini</span>
              <span class="box-val text-success">Rp {{ formatNumber(auction.current_price) }}</span>
            </div>
            <div class="price-box">
              <span class="box-lbl">Kenaikan Minimal</span>
              <span class="box-val text-indigo">+Rp {{ formatNumber(auction.bid_increment) }}</span>
            </div>
          </div>

          <!-- Bidding Form (Only visible if active and not owner) -->
          <div class="bid-action-area" v-if="auction.status === 'active'">
            <div v-if="isOwner" class="owner-notice">
              Anda adalah penjual barang lelang ini. Anda tidak dapat menaruh tawaran.
            </div>
            <div v-else-if="!authStore.isAuthenticated" class="auth-notice">
              <p>Anda harus masuk akun untuk menempatkan penawaran.</p>
              <router-link to="/login" class="btn btn-secondary btn-sm">Masuk Akun</router-link>
            </div>
            <form v-else @submit.prevent="placeBid" class="bid-form">
              <div class="form-group">
                <label class="form-label" for="bid_amount">Nominal Penawaran (Rp)</label>
                <div class="input-wrapper">
                  <span class="input-prefix">Rp</span>
                  <input 
                    type="number" 
                    id="bid_amount" 
                    v-model="bidAmount" 
                    class="form-control pad-left" 
                    :placeholder="minBidRequired"
                    :min="minBidRequired"
                    required
                    :disabled="bidLoading"
                  />
                </div>
                <span class="min-bid-hint">Tawaran minimum: Rp {{ formatNumber(minBidRequired) }}</span>
              </div>
              <button type="submit" class="btn btn-primary btn-block" :disabled="bidLoading">
                <span v-if="bidLoading" class="spinner-sm"></span>
                Kirim Penawaran
              </button>
            </form>
          </div>

          <div class="bid-action-area" v-else-if="auction.status === 'scheduled'">
            <div class="scheduled-notice">
              Lelang belum dimulai. Penawaran akan dibuka setelah hitung mundur selesai.
            </div>
          </div>
        </div>

        <!-- Bids History Card -->
        <div class="glass-card bids-history-card">
          <div class="card-header-with-badge">
            <h3>Riwayat Penawaran</h3>
            <span class="badge badge-secondary">{{ auction.bids?.length || 0 }} Penawaran</span>
          </div>

          <div class="bids-list" v-if="auction.bids && auction.bids.length > 0">
            <div 
              v-for="(bid, index) in auction.bids" 
              :key="bid.id" 
              class="bid-row"
              :class="{ 'highest-bid-row': index === 0 }"
            >
              <div class="bidder-info">
                <span class="bidder-rank" v-if="index === 0">🏆</span>
                <span class="bidder-rank-num" v-else>#{{ auction.bids.length - index }}</span>
                <div class="bidder-details">
                  <span class="bidder-name">{{ bid.bidder?.name || bid.user?.name }}</span>
                  <span class="bid-time">{{ formatTimeAgo(bid.created_at) }}</span>
                </div>
              </div>
              <span class="bid-amount" :class="{ 'text-success': index === 0 }">
                Rp {{ formatNumber(bid.amount) }}
              </span>
            </div>
          </div>
          <div v-else class="no-bids">
            <p>Belum ada penawaran. Jadilah yang pertama!</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../utils/api';
import echo from '../utils/echo';
import { useAuthStore } from '../stores/auth';
import { useNotificationStore } from '../stores/notification';
import AuctionCountdown from '../components/AuctionCountdown.vue';

const route = useRoute();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

const auctionId = route.params.id;
const auction = ref(null);
const loading = ref(true);
const bidAmount = ref('');
const bidLoading = ref(false);
const watchersCount = ref(1); // Self by default

// Winner details (when ended)
const winner = ref(null);
const winningBidAmount = ref(null);

const isOwner = computed(() => {
  if (!auction.value || !authStore.user) return false;
  return auction.value.seller_id === authStore.user.id;
});

const minBidRequired = computed(() => {
  if (!auction.value) return 0;
  const current = Number(auction.value.current_price);
  
  // If there are no bids yet, the minimum bid is the starting price.
  // Otherwise, it is current price + bid increment.
  if (!auction.value.bids || auction.value.bids.length === 0) {
    return Math.round(auction.value.start_price);
  }
  return Math.round(current + Number(auction.value.bid_increment));
});

const fetchAuctionDetails = async () => {
  try {
    const response = await api.get(`/auctions/${auctionId}`);
    auction.value = response.data.auction;
    
    // Set winner details if already ended
    if (auction.value.status === 'ended' && auction.value.highest_bid) {
      winner.value = auction.value.highest_bid.bidder;
      winningBidAmount.value = auction.value.highest_bid.amount;
    }
  } catch (error) {
    console.error('Error fetching auction details:', error);
  } finally {
    loading.value = false;
  }
};

const formatNumber = (num) => {
  return Number(num || 0).toLocaleString('id-ID');
};

const formatStatus = (status) => {
  if (status === 'active') return 'Aktif';
  if (status === 'scheduled') return 'Terjadwal';
  return 'Selesai';
};

const formatTimeAgo = (dateStr) => {
  const diff = Date.now() - new Date(dateStr).getTime();
  const seconds = Math.floor(diff / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);

  if (seconds < 60) return 'baru saja';
  if (minutes < 60) return `${minutes} menit yang lalu`;
  if (hours < 24) return `${hours} jam yang lalu`;
  return new Date(dateStr).toLocaleDateString('id-ID');
};

const placeBid = async () => {
  const amount = Number(bidAmount.value);
  if (amount < minBidRequired.value) {
    notificationStore.addToast(`Penawaran harus minimal Rp ${formatNumber(minBidRequired.value)}.`, 'error');
    return;
  }

  bidLoading.value = true;
  try {
    const response = await api.post(`/auctions/${auctionId}/bid`, { amount });
    
    // Update local state immediately with transaction result
    auction.value.current_price = response.data.auction.current_price;
    // Reload details to get updated bid list and fresh relationship
    await fetchAuctionDetails();
    
    notificationStore.addToast('Penawaran Anda berhasil ditempatkan!', 'success');
    bidAmount.value = '';
  } catch (error) {
    console.error('Error placing bid:', error);
    const msg = error.response?.data?.message || 'Gagal menempatkan penawaran. Silakan coba lagi.';
    notificationStore.addToast(msg, 'error');
  } finally {
    bidLoading.value = false;
  }
};

const handleCountdownEnded = () => {
  // Wait a second for background job to update status, then fetch fresh state
  setTimeout(fetchAuctionDetails, 1500);
};

// WebSocket channels subscription management
onMounted(async () => {
  await fetchAuctionDetails();

  if (!auction.value) return;

  // 1. Subscribe to public channel for bid updates
  echo.channel(`auction.${auctionId}`)
    .listen('BidPlaced', (e) => {
      console.log('Real-time BidPlaced event received:', e);
      // Update auction details
      auction.value.current_price = e.current_price;
      auction.value.ends_at = e.ends_at; // Anti-sniping extension updates
      auction.value.bids = e.bids;
      notificationStore.addToast(`Tawaran baru ditempatkan: Rp ${formatNumber(e.current_price)}!`, 'info');
    })
    .listen('AuctionEnded', (e) => {
      console.log('Real-time AuctionEnded event received:', e);
      auction.value.status = e.status;
      winner.value = e.winner;
      winningBidAmount.value = e.winning_bid_amount;
      notificationStore.addToast(e.message, 'success');
    });

  // 2. Subscribe to Presence Channel for online viewers count
  if (auction.value.status !== 'ended') {
    echo.join(`presence-auction.${auctionId}`)
      .here((users) => {
        watchersCount.value = users.length;
      })
      .joining((user) => {
        watchersCount.value++;
      })
      .leaving((user) => {
        watchersCount.value = Math.max(1, watchersCount.value - 1);
      });
  }
});

onUnmounted(() => {
  // Unsubscribe when leaving page
  echo.leave(`auction.${auctionId}`);
  echo.leave(`presence-auction.${auctionId}`);
});
</script>

<style scoped>
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 120px);
  gap: 16px;
}

.detail-container {
  padding: 40px 0;
}

/* Winner Banner */
.winner-banner {
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(52, 211, 153, 0.1) 100%);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 16px;
  padding: 20px 24px;
  margin-bottom: 30px;
  gap: 16px;
}

.banner-icon {
  font-size: 2.2rem;
}

.banner-content h3 {
  font-size: 1.25rem;
  color: #34d399;
  font-weight: 700;
  margin-bottom: 4px;
}

.banner-content p {
  color: var(--text-primary);
  font-size: 0.95rem;
}

/* Grid Layout */
.detail-grid {
  display: grid;
  grid-template-columns: 7fr 5fr;
  gap: 30px;
}

@media (max-width: 960px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

.left-column, .right-column {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

/* Image Card */
.image-card {
  position: relative;
  padding: 0;
  height: 420px;
  overflow: hidden;
  border-radius: 20px;
  background-color: #1e293b;
}

.detail-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 20px;
  left: 20px;
  right: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  pointer-events: none;
}

.watcher-count-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 600;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text-primary);
}

.pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: var(--accent-danger);
  animation: pulse-dot-anim 1s infinite alternate;
}

@keyframes pulse-dot-anim {
  from { opacity: 0.3; transform: scale(0.8); }
  to { opacity: 1; transform: scale(1.2); }
}

/* Description Card */
.desc-card h3 {
  font-size: 1.25rem;
  margin-bottom: 12px;
  color: var(--text-primary);
}

.desc-text {
  color: var(--text-secondary);
  font-size: 0.95rem;
  line-height: 1.6;
  margin-bottom: 24px;
}

.seller-info {
  display: flex;
  align-items: center;
  gap: 12px;
  border-top: 1px solid var(--border-color);
  padding-top: 16px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: var(--accent-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: white;
}

.seller-label {
  display: block;
  font-size: 0.7rem;
  color: var(--text-muted);
  text-transform: uppercase;
}

.seller-name {
  font-weight: 600;
  color: var(--text-primary);
}

/* Info Card */
.item-title {
  font-size: 1.8rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 20px;
  color: var(--text-primary);
}

.countdown-section {
  margin-bottom: 24px;
}

.price-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

.price-box {
  background-color: rgba(15, 23, 42, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  padding: 14px;
}

.box-lbl {
  display: block;
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-bottom: 4px;
  font-weight: 500;
}

.box-val {
  font-size: 1.2rem;
  font-weight: 800;
}

.text-success { color: #34d399; }
.text-indigo { color: #818cf8; }

.bid-action-area {
  border-top: 1px solid var(--border-color);
  padding-top: 24px;
}

.owner-notice, .scheduled-notice {
  text-align: center;
  background-color: rgba(245, 158, 11, 0.05);
  border: 1px solid rgba(245, 158, 11, 0.2);
  color: #fbbf24;
  padding: 14px;
  border-radius: 10px;
  font-size: 0.9rem;
}

.auth-notice {
  text-align: center;
  color: var(--text-secondary);
}

.auth-notice p {
  margin-bottom: 12px;
  font-size: 0.95rem;
}

.bid-form .form-group {
  margin-bottom: 16px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-prefix {
  position: absolute;
  left: 16px;
  color: var(--text-secondary);
  font-weight: 600;
}

.pad-left {
  padding-left: 44px;
  font-size: 1.1rem;
  font-weight: 700;
}

.min-bid-hint {
  display: block;
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 6px;
}

.btn-block {
  width: 100%;
}

/* Bids History Card */
.card-header-with-badge {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.bids-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 350px;
  overflow-y: auto;
  padding-right: 4px;
}

.bid-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: rgba(15, 23, 42, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.02);
  border-radius: 10px;
}

.highest-bid-row {
  background: rgba(52, 211, 153, 0.05);
  border-color: rgba(52, 211, 153, 0.2);
}

.bidder-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.bidder-rank {
  font-size: 1.2rem;
}

.bidder-rank-num {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--text-muted);
  background: var(--bg-secondary);
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bidder-details {
  display: flex;
  flex-direction: column;
}

.bidder-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-primary);
}

.bid-time {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.bid-amount {
  font-size: 0.95rem;
  font-weight: 700;
}

.no-bids {
  text-align: center;
  padding: 30px;
  color: var(--text-muted);
}

.spinner-lg {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(99, 102, 241, 0.1);
  border-radius: 50%;
  border-top-color: var(--accent-primary);
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes slideIn {
  from { transform: translateY(-20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.animate-slide-in {
  animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
