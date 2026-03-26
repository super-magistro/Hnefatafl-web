export default defineAppConfig({
    ui: {
        colors: {
            primary: 'vert',
            neutral: 'spring-wood',
            warning: 'golden-grass',
            error: 'red'
        },

        // 1. On configure le composant UCard pour remplacer ta div principale
        card: {
            slots: {
                root: 'bg-white shadow-xl rounded-2xl ring-1 ring-neutral-200',
                body: 'p-8 sm:p-8',
            }
        },

        // 2. On configure toutes les déclinaisons de boutons ici
        button: {
            variants: {
                variant: {
                    // Le gros bouton vert 3D
                    cta: 'font-bold tracking-wider text-white uppercase bg-primary-700 hover:bg-primary-800 transition-all duration-200',

                    // L'onglet sélectionné
                    tabActive: 'font-bold text-white bg-primary-700 shadow hover:bg-primary-800 transition-all duration-200',

                    // L'onglet inactif
                    tabInactive: 'font-bold text-oil-950 bg-transparent hover:bg-neutral-200 border-none transition-all duration-200',

                    // Le lien "Mot de passe oublié"
                    Link: 'font-medium text-pine-cone-600 hover:text-primary-700 bg-transparent transition-colors p-0'
                }
            }
        }
    }
})