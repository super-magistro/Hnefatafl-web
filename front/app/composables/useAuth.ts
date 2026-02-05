export const useAuth = () => {
    const config = useRuntimeConfig()

    // On crée un cookie 'auth_token' qui survivra si on ferme le navigateur
    const token = useCookie('auth_token')

    // Une variable d'état pour savoir si on est connecté
    const user = useState('user', () => null)


    const login = async (email: string, password: string) => {
        try {

            // Appel à Symfony
            const response = await $fetch(`${config.public.apiBase}/login_check`, {
                method: 'POST',
                body: {
                    "email": email,
                    "password": password
                }
            } as any) as { token: string }


            // Si ça marche, on sauvegarde le token
            token.value = response.token
            return true
        } catch (error) {
            console.error('Login échoué', error)
            return false
        }
    }

    const logout = () => {
        token.value = null
        user.value = null
    }

    // On retourne ce qui doit être accessible aux composants
    return { token, login, logout }
}