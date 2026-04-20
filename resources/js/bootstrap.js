import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.Pusher = Pusher;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    const forceTls = (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https';
    const defaultPort = forceTls ? 443 : 80;
    const parsedPort = Number(import.meta.env.VITE_REVERB_PORT || defaultPort);

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: Number.isFinite(parsedPort) ? parsedPort : defaultPort,
        wssPort: Number.isFinite(parsedPort) ? parsedPort : 443,
        forceTLS: forceTls,
        enabledTransports: ['ws', 'wss'],
    });
}
