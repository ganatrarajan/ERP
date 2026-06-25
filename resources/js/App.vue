<template>
    <div class="h-full">
        <!-- Render current route matching layout -->
        <component :is="currentLayout">
            <router-view />
        </component>

        <!-- Global Custom Confirm Modal -->
        <ConfirmModal />

        <!-- Global Custom Toast List -->
        <ToastList />
    </div>
</template>

<script>
import { computed, watch, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useToastStore } from './stores/toast';
import AppLayout from './layouts/AppLayout.vue';
import AuthLayout from './layouts/AuthLayout.vue';
import ConfirmModal from './components/ConfirmModal.vue';
import ToastList from './components/ToastList.vue';

export default {
    name: 'App',
    components: {
        AppLayout,
        AuthLayout,
        ConfirmModal,
        ToastList
    },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const currentLayout = computed(() => {
            const layoutType = route.meta?.layout || 'app';
            return layoutType === 'auth' ? 'AuthLayout' : 'AppLayout';
        });

        // Auto-logout after 15 minutes of inactivity
        const INACTIVITY_TIMEOUT = 15 * 60 * 1000; // 15 minutes
        const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
        let checkInterval = null;
        let lastWriteTime = 0;

        const updateActivity = () => {
            localStorage.setItem('lastActiveTime', Date.now().toString());
        };

        const handleUserActivity = () => {
            const now = Date.now();

            // Check if they already exceeded inactivity before we update
            const lastActive = localStorage.getItem('lastActiveTime');
            if (lastActive) {
                const inactiveDuration = now - parseInt(lastActive, 10);
                if (inactiveDuration >= INACTIVITY_TIMEOUT) {
                    logoutUser();
                    return;
                }
            }

            if (now - lastWriteTime > 2000) { // Throttle writing to localStorage (every 2 seconds)
                lastWriteTime = now;
                updateActivity();
            }
        };

        const logoutUser = async () => {
            stopInactivityMonitoring();
            try {
                await authStore.logout();
                toastStore.info('You have been logged out due to inactivity.');
                router.push({ name: 'login' });
            } catch (e) {
                console.error('Auto-logout failed:', e);
            }
        };

        const checkInactivity = () => {
            if (!authStore.authenticated) return;
            const lastActive = localStorage.getItem('lastActiveTime');
            if (lastActive) {
                const inactiveDuration = Date.now() - parseInt(lastActive, 10);
                if (inactiveDuration >= INACTIVITY_TIMEOUT) {
                    logoutUser();
                }
            } else {
                updateActivity();
            }
        };

        const startInactivityMonitoring = () => {
            // Check if they already exceeded inactivity before setting up monitoring
            const lastActive = localStorage.getItem('lastActiveTime');
            if (lastActive) {
                const inactiveDuration = Date.now() - parseInt(lastActive, 10);
                if (inactiveDuration >= INACTIVITY_TIMEOUT) {
                    logoutUser();
                    return;
                }
            }

            updateActivity();
            activityEvents.forEach(event => {
                window.addEventListener(event, handleUserActivity, { passive: true });
            });
            if (checkInterval) clearInterval(checkInterval);
            checkInterval = setInterval(checkInactivity, 5000); // Check every 5 seconds
        };

        const stopInactivityMonitoring = () => {
            activityEvents.forEach(event => {
                window.removeEventListener(event, handleUserActivity);
            });
            if (checkInterval) {
                clearInterval(checkInterval);
                checkInterval = null;
            }
            localStorage.removeItem('lastActiveTime');
        };

        watch(() => authStore.authenticated, (isAuthenticated) => {
            if (isAuthenticated) {
                startInactivityMonitoring();
            } else {
                stopInactivityMonitoring();
            }
        }, { immediate: true });

        onUnmounted(() => {
            stopInactivityMonitoring();
        });

        return {
            currentLayout
        };
    }
}
</script>
