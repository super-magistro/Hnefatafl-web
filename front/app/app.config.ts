// app.config.ts
export default defineAppConfig({
    ui: {
        colors: {
            primary: 'vert',
            neutral: 'spring-wood',
            warning: 'golden-grass',
            error: 'red'
        },

        card: {
            slots: {
                // Ton style par défaut (pour la grosse carte principale)
                root: 'bg-white shadow-xl rounded-2xl ring-1 ring-neutral-200',
                body: 'p-8 sm:p-12',
            },
            variants: {
                variant: {
                    // Une nouvelle variante pour les cartes intérieures !
                    subtle: {
                        root: 'bg-neutral-50 shadow-none ring-1 ring-neutral-200',
                        body: 'p-6 sm:p-8'
                    }
                }
            }
        },

        alert: {
            slots: {
                root: 'py-5',
                title: 'text-base font-bold uppercase tracking-wider mb-2',
                description: 'text-base leading-relaxed',
                icon: 'w-6 h-6 shrink-0'
            }
        },

        separator: {
            slots: {
                border: 'border-neutral-200'
            }
        },

        button: {
            variants: {
                variant: {
                    cta: 'font-bold tracking-wider text-white uppercase bg-primary-700 hover:bg-primary-800 transition-all duration-200',
                    tabActive: 'font-bold text-white bg-primary-700 shadow hover:bg-primary-800 transition-all duration-200',
                    tabInactive: 'font-bold text-oil-950 bg-transparent hover:bg-neutral-200 border-none transition-all duration-200',
                    Link: 'font-medium text-pine-cone-600 hover:text-primary-700 bg-transparent transition-colors p-0',
                    sidebarButton: 'w-full justify-start py-2 px-3 font-medium text-spring-wood-100 hover:text-white hover:bg-vert-900/50 transition-colors rounded-md bg-transparent'
                }
            }
        },

        sidebar: {
            slots: {
                inner: 'bg-vert-800 border-none h-full',
                header: 'h-[80px] px-6 flex items-center shrink-0',
                body: 'px-4 flex-1',
                footer: 'p-4 border-t border-vert-700/50 shrink-0'
            }
        },

        navigationMenu: {
            slots: {
                link: 'py-2 px-3 mb-1 font-medium text-spring-wood-100 hover:text-white hover:bg-vert-900/50 transition-colors rounded-md bg-transparent',
                linkActive: 'text-golden-grass-500 hover:text-golden-grass-400 bg-vert-900/80 font-bold shadow-sm',
                icon: 'w-5 h-5 text-current opacity-80 group-hover:opacity-100 transition-opacity'
            }
        }
    }
})