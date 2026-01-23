<?php

namespace App\Security;

class SecureRules
{

    /**
     * Accès public (Inscription, Login, Documentation)
     */
    public const PUBLIC_ACCESS = "is_granted('PUBLIC_ACCESS')";

    /**
     * Lecture standard : Nécessite seulement d'être connecté
     */
    public const USER_READ = "is_granted('ROLE_USER')";

    /**
     * Modification de profil : Nécessite d'être le propriétaire du compte ou un Admin.
     * ATTENTION : Cela nécessite que le UserVoter soit en place.
     */
    public const USER_EDIT = "is_granted('ROLE_USER_EDIT', object)";

    /**
     * Création de contenu (Parties, etc.)
     * Par défaut, un ROLE_USER suffit, mais on peut restreindre si besoin.
     */
    public const CONTENT_CREATE = "is_granted('ROLE_USER')";

    /**
     * Administration critique (Suppression d'utilisateurs, gestion des variantes de jeu)
     */
    public const ADMIN_ONLY = "is_granted('ROLE_ADMIN')";

    public const MSG_USER_READ = "Vous devez être connecté pour accéder à ces données.";

    public const MSG_USER_EDIT = "Vous n'êtes pas autorisé à modifier ce profil (ce n'est pas le vôtre).";

    public const MSG_CONTENT_CREATE = "Vos droits ne vous permettent pas de créer du contenu.";

    public const MSG_ADMIN_ONLY = "Action refusée : privilèges d'administrateur requis.";
}
