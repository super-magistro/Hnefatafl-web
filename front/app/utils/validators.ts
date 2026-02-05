/**
 * Vérifie si une chaîne est un email valide
 */
export const isValidEmail = (email: string): boolean => {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Vérifie si un mot de passe est assez fort (min 6 caractères ici)
 */
export const isStrongPassword = (password: string): boolean => {
    return password.length >= 6;
}

