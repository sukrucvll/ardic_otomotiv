<?php

namespace App\Support;

/**
 * Departman listesi Personel Sistemi'nde tanımlı; portal her departmana
 * adından türetilen sabit bir renk verir.
 */
class DepartmentColor
{
    private const PALETTE = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

    public static function for(?string $department): string
    {
        return self::PALETTE[crc32((string) $department) % count(self::PALETTE)];
    }
}
