export const useMe = () => {
    const config = useRuntimeConfig()

    const token = useCookie('auth_token', { maxAge: 2592000 })

    const user = useState<any | null>('user', () => null)

    const fetchMe = async () => {
        // Sécurité : si on n'a pas de token, inutile d'interroger le serveur
        if (!token.value) {
            user.value = null
            return null
        }

        try {
            user.value = await $fetch(`${config.public.apiBase}/me`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token.value}`,
                    'Accept': 'application/ld+json' // ou 'application/json'
                }
            } as any) as { user: string }

            return true

        } catch (error: any) {
            console.error('Erreur lors de la récupération du profil:', error)

            token.value = null
            user.value = null
            navigateTo('/login', { replace: true })
            return false
        }
    }

    return { user, fetchMe }
}