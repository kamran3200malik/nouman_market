import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import Swal from 'sweetalert2';
import { storageUrl } from './Utils/storage';

const appName = import.meta.env.VITE_APP_NAME || 'BeautyBook Luxe';

createInertiaApp({
    title: (title) => {
        if (!title) return appName;
        const trimmed = title.trim();
        if (
            trimmed.toLowerCase().includes(appName.toLowerCase()) ||
            trimmed.toLowerCase().includes('beautybook')
        ) {
            return trimmed;
        }
        return `${trimmed} - ${appName}`;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.$storageUrl = storageUrl;
        return app
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#E11D48',
    },
});

const showToast = (flash) => {
    if (!flash) return;

    const successMsg = flash.success;
    const errorMsg = flash.error;
    const infoMsg = flash.info;
    const warningMsg = flash.warning;

    if (!successMsg && !errorMsg && !infoMsg && !warningMsg) return;

    if (successMsg) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: successMsg,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
        flash.success = null;
    } else if (errorMsg) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: errorMsg,
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true,
        });
        flash.error = null;
    } else if (infoMsg) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: infoMsg,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
        flash.info = null;
    } else if (warningMsg) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: warningMsg,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
        flash.warning = null;
    }
};

router.on('success', (event) => {
    const flash = event.detail.page?.props?.flash;
    if (flash) {
        showToast(flash);
    }
});

router.on('navigate', (event) => {
    const flash = event.detail.page?.props?.flash;
    if (flash) {
        showToast(flash);
    }
});

router.on('invalid', (event) => {
    if (event.detail.response?.status === 419) {
        event.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Session Expired',
            text: 'Your session has expired or your login changed. Reloading page...',
            timer: 2500,
            showConfirmButton: false,
        }).then(() => {
            window.location.reload();
        });
    }
});
