<?php

namespace App\Config;

class GameRules
{
    public const KEY_WIN_CONDITION     = 'win_condition';    // Comment le Roi gagne ?
    public const KEY_KING_CAPTURE      = 'king_capture';     // Comment tuer le Roi ?
    public const KEY_KING_WEAPON       = 'king_weapon';      // Le Roi peut-il capturer ?
    public const KEY_THRONE_HOSTILITY  = 'throne_hostility'; // Le trône est-il dangereux ?

    public const WIN_CORNER = 'corner'; // Le Roi doit atteindre un des 4 coins (Standard)
    public const WIN_EDGE   = 'edge';   // Le Roi doit atteindre n'importe quel bord du plateau

    public const CAPTURE_2_SIDES = '2_sides'; // Prise en sandwich classique (Roi faible)
    public const CAPTURE_4_SIDES = '4_sides'; // Doit être entouré sur les 4 côtés (Roi fort)

    public const KING_ARMED   = 'armed';    // Le Roi peut capturer des ennemis
    public const KING_UNARMED = 'unarmed';  // Le Roi ne peut pas capturer

    public const THRONE_ALWAYS_HOSTILE = 'always'; // Hostile pour tout le monde, tout le temps
    public const THRONE_HOSTILE_EMPTY  = 'empty';  // Hostile uniquement s'il est vide (si le Roi est dessus, il protège)
    public const THRONE_NEVER_HOSTILE  = 'never';  // Le trône est un refuge sûr

    public static function getRulesForVariant(string $variantName): array
    {
        return match ($variantName) {
            'Brandubh' => [ // Petit plateau 7x7
                self::KEY_WIN_CONDITION    => self::WIN_CORNER,
                self::KEY_KING_CAPTURE     => self::CAPTURE_4_SIDES,
                self::KEY_KING_WEAPON      => self::KING_ARMED,
                self::KEY_THRONE_HOSTILITY => self::THRONE_ALWAYS_HOSTILE,
            ],
            'Tablut' => [ // Plateau 9x9
                self::KEY_WIN_CONDITION    => self::WIN_CORNER, // Parfois joué en 'edge' selon les versions
                self::KEY_KING_CAPTURE     => self::CAPTURE_4_SIDES,
                self::KEY_KING_WEAPON      => self::KING_ARMED,
                self::KEY_THRONE_HOSTILITY => self::THRONE_HOSTILE_EMPTY,
            ],
            'Hnefatafl' => [ // Plateau 11x11 (Fetlar)
                self::KEY_WIN_CONDITION    => self::WIN_CORNER,
                self::KEY_KING_CAPTURE     => self::CAPTURE_4_SIDES,
                self::KEY_KING_WEAPON      => self::KING_UNARMED, // Souvent désarmé en Fetlar
                self::KEY_THRONE_HOSTILITY => self::THRONE_HOSTILE_EMPTY,
            ],
            // Par défaut (Sécurité)
            default => [
                self::KEY_WIN_CONDITION    => self::WIN_CORNER,
                self::KEY_KING_CAPTURE     => self::CAPTURE_4_SIDES,
                self::KEY_KING_WEAPON      => self::KING_ARMED,
                self::KEY_THRONE_HOSTILITY => self::THRONE_HOSTILE_EMPTY,
            ]
        };
    }
}
