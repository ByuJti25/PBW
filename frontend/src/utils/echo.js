import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY || 'tyvs55vdceifflncuekb',
  wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
  wsPort: import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : 8080,
  wssPort: import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : 8080,
  forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
  enabledTransports: ['ws', 'wss'],
  authorizer: (channel, options) => {
    return {
      authorize: (socketId, callback) => {
        // Always get the latest token from localStorage
        const token = localStorage.getItem('token');
        const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
        
        // Remove '/api' suffix if present to get base URL, since Broadcast::routes registers at '/broadcasting/auth'
        const authUrl = apiBase.replace('/api', '') + '/broadcasting/auth';

        axios.post(authUrl, {
          socket_id: socketId,
          channel_name: channel.name
        }, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          }
        })
        .then(response => {
          callback(false, response.data);
        })
        .catch(error => {
          console.error('WebSocket auth error:', error);
          callback(true, error);
        });
      }
    };
  }
});

export default echo;
