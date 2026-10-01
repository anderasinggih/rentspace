import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

try {
    const pusherKey = '8536c6c758470a793654';
    const pusherCluster = 'ap1';

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
    });

    console.log('[Echo/Pusher] WebSocket Client connected via Pusher Cloud (Cluster: ap1)');
} catch (e) {
    console.warn('[Echo/Pusher] Failed to initialize Pusher Echo:', e);
}
