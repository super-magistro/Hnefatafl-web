// nuxt.config.ts
export default defineNuxtConfig({
  devtools: { enabled: true },
  modules: ['@nuxt/ui', '', '@nuxtjs/google-fonts'],
  ui: {
    colorMode: false
  },
  runtimeConfig: {
    public: { apiBase: 'http://localhost:8000/api' }
  },
  components: [
    { path: '~/components/atoms', prefix: 'Atoms' },
    { path: '~/components/molecules', prefix: 'Molecules' },
    { path: '~/components/organisms', prefix: 'Organisms' },
    '~/components'
  ],
  css: ['~/assets/css/main.css'],
  googleFonts: {
    families: {
      'Cinzel': true,
      'Inter': true,
    }
  }
})