import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { api } from './api'

window.Pusher = Pusher

const key = import.meta.env.VITE_REVERB_APP_KEY

export const echo = key ? new Echo({
  broadcaster: 'reverb',
  authorizer: (channel) => ({ authorize: (socketId, callback) => {
    api('/realtime/auth', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }) })
      .then(async (response) => { if (!response.ok) throw new Error('Không thể xác thực realtime'); return response.json() })
      .then((data) => callback(null, data)).catch((error) => callback(error, null))
  } }),
  key,
  wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
}) : null
