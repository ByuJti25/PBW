<template>
  <div class="countdown-display" :class="statusClass">
    <div class="countdown-label">{{ labelText }}</div>
    <div v-if="!isEnded" class="countdown-timer">
      <div class="time-block">
        <span class="time-val">{{ pad(timeLeft.days) }}</span>
        <span class="time-lbl">hari</span>
      </div>
      <span class="divider">:</span>
      <div class="time-block">
        <span class="time-val">{{ pad(timeLeft.hours) }}</span>
        <span class="time-lbl">jam</span>
      </div>
      <span class="divider">:</span>
      <div class="time-block">
        <span class="time-val">{{ pad(timeLeft.minutes) }}</span>
        <span class="time-lbl">menit</span>
      </div>
      <span class="divider">:</span>
      <div class="time-block">
        <span class="time-val" :class="{ 'pulse-danger': isEndingSoon }">{{ pad(timeLeft.seconds) }}</span>
        <span class="time-lbl">detik</span>
      </div>
    </div>
    <div v-else class="countdown-ended">
      <span>Lelang Selesai</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  startsAt: {
    type: String,
    required: true,
  },
  endsAt: {
    type: String,
    required: true,
  },
  status: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['countdown-ended']);

const timeLeft = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
const isEnded = ref(props.status === 'ended');
let timerId = null;

const labelText = computed(() => {
  if (props.status === 'scheduled') {
    return 'Dimulai dalam:';
  }
  if (props.status === 'active') {
    return 'Berakhir dalam:';
  }
  return 'Status:';
});

const statusClass = computed(() => {
  if (props.status === 'scheduled') return 'status-scheduled';
  if (props.status === 'active') return isEndingSoon.value ? 'status-active-danger' : 'status-active';
  return 'status-ended';
});

const isEndingSoon = computed(() => {
  if (props.status !== 'active') return false;
  // If less than 60 seconds remaining
  const target = new Date(props.endsAt).getTime();
  const now = new Date().getTime();
  const diff = target - now;
  return diff > 0 && diff < 60000;
});

const pad = (num) => {
  return String(num).padStart(2, '0');
};

const calculateTimeLeft = () => {
  const now = new Date().getTime();
  let target;

  if (props.status === 'scheduled') {
    target = new Date(props.startsAt).getTime();
  } else if (props.status === 'active') {
    target = new Date(props.endsAt).getTime();
  } else {
    isEnded.value = true;
    return;
  }

  const diff = target - now;

  if (diff <= 0) {
    timeLeft.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
    isEnded.value = true;
    clearInterval(timerId);
    emit('countdown-ended');
    return;
  }

  isEnded.value = false;
  timeLeft.value = {
    days: Math.floor(diff / (1000 * 60 * 60 * 24)),
    hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
    minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
    seconds: Math.floor((diff % (1000 * 60)) / 1000),
  };
};

// Re-calculate when endsAt changes (e.g. anti-sniping trigger)
watch(() => props.endsAt, () => {
  calculateTimeLeft();
});

watch(() => props.status, (newStatus) => {
  if (newStatus === 'ended') {
    isEnded.value = true;
    clearInterval(timerId);
  } else {
    calculateTimeLeft();
  }
});

onMounted(() => {
  calculateTimeLeft();
  timerId = setInterval(calculateTimeLeft, 1000);
});

onUnmounted(() => {
  if (timerId) clearInterval(timerId);
});
</script>

<style scoped>
.countdown-display {
  display: flex;
  flex-direction: column;
  background: rgba(15, 23, 42, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 10px 14px;
}

.countdown-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--text-secondary);
  text-transform: uppercase;
  margin-bottom: 6px;
  letter-spacing: 0.05em;
}

.countdown-timer {
  display: flex;
  align-items: center;
  gap: 4px;
}

.time-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 32px;
}

.time-val {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  font-family: monospace;
}

.time-lbl {
  font-size: 0.6rem;
  color: var(--text-muted);
  text-transform: uppercase;
}

.divider {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-muted);
  align-self: flex-start;
  margin-top: -2px;
}

.countdown-ended {
  font-size: 1rem;
  font-weight: 700;
  color: var(--accent-danger);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 4px 0;
}

.status-active-danger {
  border-color: rgba(239, 68, 68, 0.3);
  background: rgba(239, 68, 68, 0.05);
}

.status-active-danger .time-val {
  color: #f87171;
}

.pulse-danger {
  animation: pulse 1s infinite alternate;
}

@keyframes pulse {
  from { opacity: 0.5; }
  to { opacity: 1; color: #ef4444; text-shadow: 0 0 8px rgba(239, 68, 68, 0.5); }
}
</style>
