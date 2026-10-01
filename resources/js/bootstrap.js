import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

try {
    const isHttps = window.location.protocol === 'https:';
    const currentHost = window.location.hostname;
    
    // Jika dibuka di domain live (rentspace.id / domain VPS), konek ke host tersebut dan port 8080
    // Reverb running on host:0.0.0.0 port:8080
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: 'bmkxdqurg6bgcqiiuj24',
        wsHost: currentHost,
        wsPort: 8080,
        wssPort: 8080,
        forceTLS: isHttps,
        enabledTransports: ['ws', 'wss'],
    });

    console.log('[Echo/Reverb] WebSocket Client connected to', currentHost + ':8080', 'TLS:', isHttps);
} catch (e) {
    console.warn('[Echo/Reverb] Failed to initialize WebSocket Echo:', e);
}
