export const useAuth = () => {
  const config = useRuntimeConfig()

  // Crée un cookie réactif "auth_token" qui persiste même si on ferme le navigateur
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 7 // 1 semaine
  })

  // Fonction de Login
  const login = async (email: string, password: string) => {
    try {
      // $fetch gère automatiquement le JSON.stringify et les headers
      const data = await $fetch<{ token: string }>(`${config.public.apiBase}/login_check`, {
        method: 'POST',
        body: {
          email: email, // Symfony attend "email" (d'après ton curl)
          password: password
        }
      })

      // Si succès, on stocke le token dans le cookie
      token.value = data.token

      return true // Succès
    } catch (err) {
      console.error('Erreur login:', err)
      return false // Échec
    }
  }

  // Fonction de Logout (bonus)
  const logout = () => {
    token.value = null
    navigateTo('/login')
  }

  return {
    token,
    login,
    logout
  }
}