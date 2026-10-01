import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

try {
    const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const reverbPort = import.meta.env.VITE_REVERB_PORT || (window.location.protocol === 'https:' ? 443 : 8080);
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || 'bmkxdqurg6bgcqiiuj24';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    console.log('[Echo/Reverb] WebSocket Client initialized for', reverbHost);
} catch (e) {
    console.warn('[Echo/Reverb] Failed to initialize WebSocket Echo:', e);
}
