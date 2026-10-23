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
}
