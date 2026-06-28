import { defineStore } from 'pinia';

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    toasts: [],
  }),
  actions: {
    addToast(message, type = 'info', duration = 5000) {
      const id = Date.now() + Math.random().toString(36).substr(2, 9);
      this.toasts.push({ id, message, type });

      if (duration > 0) {
        setTimeout(() => {
          this.removeToast(id);
        }, duration);
      }
    },
    removeToast(id) {
      this.toasts = this.toasts.filter((t) => t.id !== id);
    },
  },
});
