import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loading: false,
        impersonating: false,
        activeModules: [],
    }),

    getters: {
        authenticated: (state) => !!state.user,
        permissions: (state) => {
            if (!state.user) return [];
            // Flatten permissions from roles and direct permissions
            const rolePerms = state.user.roles?.flatMap(r => r.permissions?.map(p => p.name) || []) || [];
            const directPerms = state.user.permissions?.map(p => p.name) || [];
            const allPerms = [...new Set([...rolePerms, ...directPerms])];

            if (state.user.roles?.some(r => r.name === 'Super Admin') || state.impersonating) {
                return allPerms;
            }

            // Map permission prefixes to module slugs
            const modulePermissionMap = {
                'student': 'students',
                'promotion': 'students',
                'academic_year': 'academics',
                'class': 'academics',
                'section': 'academics',
                'teacher': 'teachers',
                'attendance': 'attendance',
                'homework': 'homework',
                'fees': 'fees',
                'exam': 'examinations',
                'exam_schedule': 'examinations',
                'marks': 'examinations',
                'result': 'examinations',
                'report_card': 'examinations',
                'report_card_setup': 'examinations',
                'subject': 'subjects',
                'notice': 'notices'
            };

            const activeSlugs = state.activeModules.map(m => m.toLowerCase());

            return allPerms.filter(perm => {
                const prefix = perm.split('.')[0];
                const requiredModule = modulePermissionMap[prefix];
                if (requiredModule) {
                    return activeSlugs.includes(requiredModule.toLowerCase());
                }
                return true;
            });
        },
        roles: (state) => {
            if (!state.user) return [];
            return state.user.roles?.map(r => r.name) || [];
        },
        isSuperAdmin: (state) => {
            if (!state.user) return false;
            return state.user.roles?.some(r => r.name === 'Super Admin') || false;
        }
    },

    actions: {
        async fetchUser() {
            this.loading = true;
            try {
                const response = await window.axios.get('/api/auth/me');
                this.user = response.data.user;
                this.impersonating = response.data.impersonating;
                this.activeModules = response.data.active_modules || [];
                return this.user;
            } catch (error) {
                this.user = null;
                this.impersonating = false;
                this.activeModules = [];
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async login(credentials) {
            this.loading = true;
            try {
                await window.axios.get('/sanctum/csrf-cookie');
                const response = await window.axios.post('/api/auth/login', credentials);
                this.user = response.data.user;
                this.impersonating = response.data.impersonating;
                this.activeModules = response.data.active_modules || [];
                return response.data;
            } catch (error) {
                this.user = null;
                this.impersonating = false;
                this.activeModules = [];
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await window.axios.post('/api/auth/logout');
            } catch (error) {
                console.error('Logout error', error);
            } finally {
                this.user = null;
                this.impersonating = false;
                this.activeModules = [];
            }
        },

        hasPermission(permissionName) {
            if (this.isSuperAdmin || this.impersonating) return true;
            return this.permissions.includes(permissionName);
        },

        hasRole(roleName) {
            return this.roles.includes(roleName);
        },

        hasModule(moduleSlug) {
            if (!this.user) return false;
            if (this.isSuperAdmin) return true;
            return this.activeModules.includes(moduleSlug.toLowerCase());
        }
    }
});
