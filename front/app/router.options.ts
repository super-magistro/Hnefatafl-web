import type { RouterConfig } from '@nuxt/schema'

export default <RouterConfig> {
    routes: (_routes) => [
        {
            name: 'home',
            path: '/',
            component: () => import('~/pages/index.vue')
        },
        {
            name: 'login',
            path: '/login',
            component: () => import('~/pages/login.vue')
        },
    ],
}