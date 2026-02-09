// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },

  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api'
    }
  },

  // Auto-import des composants atomiques
  components: [
    { path: '~/components/atoms', prefix: 'Atoms' },
    { path: '~/components/molecules', prefix: 'Molecules' },
    { path: '~/components/organisms', prefix: 'Organisms' },
    '~/components'
  ],

  css: ['~/assets/less/main.less'],

  vite: {
    css: {
      preprocessorOptions: {
        less: {
          additionalData: '@import "@/assets/less/_variables.less";'
        }
      }
    }
  },

  app: {
    head: {
      link: [
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@400;500;600&display=swap' }
      ]
    }
  }
})