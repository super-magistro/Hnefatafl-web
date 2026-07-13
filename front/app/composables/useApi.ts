export const useApi = () => {
    const config = useRuntimeConfig()
    const token = useCookie('auth_token', { maxAge: 2592000 })

    // apiFetch encapsule les appels à l'API Symfony en ajoutant automatiquement 
    // le token JWT d'authentification s'il est présent, ainsi que les headers appropriés.
    const apiFetch = async <T = any>(path: string, options: any = {}): Promise<T> => {
        const headers = {
            'Accept': 'application/ld+json',
            'Content-Type': 'application/json',
            ...options.headers,
        } as Record<string, string>

        if (token.value) {
            headers['Authorization'] = `Bearer ${token.value}`
        }

        const cleanPath = path.startsWith('/api/') ? path.substring(4) : path
        const url = path.startsWith('http') ? path : `${config.public.apiBase}${cleanPath}`

        try {
            return await $fetch<T>(url, {
                ...options,
                headers,
            })
        } catch (error: any) {
            if (error?.status === 401) {
                token.value = null
                const userState = useState('user')
                if (userState) userState.value = null
                navigateTo('/login', { replace: true })
            }
            throw error
        }
    }

    return { apiFetch }
}
