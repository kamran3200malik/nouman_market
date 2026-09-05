<?php

namespace App;

enum ProfessionalType: string
{
    case MAKEUP_ARTIST = 'makeup_artist';
    case BRIDAL_MAKEUP_ARTIST = 'bridal_makeup_artist';
    case HAIR_STYLIST = 'hair_stylist';
    case NAIL_ARTIST = 'nail_artist';
    case FACIAL_SPECIALIST = 'facial_specialist';
    case MEHNDI_ARTIST = 'mehndi_artist';
    case LASH_ARTIST = 'lash_artist';
    case BROW_ARTIST = 'brow_artist';
    case BEAUTY_SALON = 'beauty_salon';
    case SPA = 'spa';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::MAKEUP_ARTIST => 'Makeup Artist',
            self::BRIDAL_MAKEUP_ARTIST => 'Bridal Makeup Artist',
            self::HAIR_STYLIST => 'Hair Stylist',
            self::NAIL_ARTIST => 'Nail Artist',
            self::FACIAL_SPECIALIST => 'Facial Specialist',
            self::MEHNDI_ARTIST => 'Mehndi Artist',
            self::LASH_ARTIST => 'Lash Artist',
            self::BROW_ARTIST => 'Brow Artist',
            self::BEAUTY_SALON => 'Beauty Salon',
            self::SPA => 'Spa',
            self::OTHER => 'Other',
        };
    }

    public function getCategory(): string
    {
        return match ($this) {
            self::MAKEUP_ARTIST, self::BRIDAL_MAKEUP_ARTIST => 'makeup',
            self::HAIR_STYLIST => 'hair',
            self::NAIL_ARTIST => 'nails',
            self::FACIAL_SPECIALIST => 'skin',
            self::MEHNDI_ARTIST => 'mehndi',
            self::LASH_ARTIST => 'lashes',
            self::BROW_ARTIST => 'brows',
            self::BEAUTY_SALON, self::SPA => 'salon',
            self::OTHER => 'other',
        };
    }
}
