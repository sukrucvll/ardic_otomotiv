<?php

namespace App\Support;

/**
 * Doğrulama hatasından sonra forma geri basılan eski girdi (old()). Değer
 * kullanıcıdan geldiği için beklenen türde olmayabilir (ör. metin alanına
 * dizi); bu durumda sayfa çökmesin diye varsayılan değer kullanılır.
 */
class OldInput
{
    public static function text(string $key, mixed $default = null): mixed
    {
        $value = old($key, $default);

        return is_array($value) ? $default : $value;
    }

    /**
     * Çoklu seçim (checkbox) alanları için.
     *
     * @return array<int|string, mixed>
     */
    public static function list(string $key): array
    {
        $value = old($key, []);

        return is_array($value) ? array_filter($value, is_scalar(...)) : [];
    }
}
