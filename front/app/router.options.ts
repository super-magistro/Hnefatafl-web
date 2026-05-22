import type { RouterConfig } from '@nuxt/schema'

export default <RouterConfig> {
    routes: (_routes) => [
        {
            name: 'index',
            path: '/',
            component: () => import('~/pages/index.vue'),
        },
        {
            name: 'login',
            path: '/login',
            component: () => import('~/pages/login.vue'),
        },
        {
            name: 'main',
            path: '/main',
            component: () => import('~/pages/main.vue'),
            meta: { layout: 'sidebar' }
        },
        {
            name: 'rules',
            path: '/rules',
            component: () => import('~/pages/rules.vue'),
            meta: { layout: 'sidebar' }
        },
        {
            name: 'games',
            path: '/games',
            component: () => import('~/pages/games.vue'),
            meta: { layout: 'sidebar' }
        },
        {
            name: 'settings',
            path: '/settings',
            component: () => import('~/pages/settings.vue'),
            meta: { layout: 'sidebar' }
        },
    ],
}