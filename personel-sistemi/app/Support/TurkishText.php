<?php

namespace App\Support;

use Illuminate\Support\Str;
use PDO;
use Pdo\Sqlite;

class TurkishText
{
    /**
     * Türkçe alfabe sırası için harflerin karşılıkları: ç, c'den sonra gelir;
     * ı ise h ile i arasındadır.
     */
    private const SORT_MAP = ['ç' => 'c~', 'ğ' => 'g~', 'ı' => 'h~', 'ö' => 'o~', 'ş' => 's~', 'ü' => 'u~'];

    /**
     * Türkçe kurallarına göre küçük harfe çevirir (İ → i, I → ı).
     */
    public static function lower(?string $text): string
    {
        return Str::lower(str_replace(['İ', 'I'], ['i', 'ı'], (string) $text));
    }

    /**
     * Türkçe alfabe sırasına göre karşılaştırır (büyük/küçük harf duyarsız).
     */
    public static function compare(?string $a, ?string $b): int
    {
        return strcmp(strtr(self::lower($a), self::SORT_MAP), strtr(self::lower($b), self::SORT_MAP));
    }

    /**
     * SQLite'ın LIKE ve lower() işlemleri yalnızca ASCII harflerde büyük/küçük harf
     * duyarsızdır ("çelik" ile "Çelik" eşleşmez). Türkçe arama ve sıralama için
     * bağlantıya tr_lower() fonksiyonu ve "turkish" karşılaştırma kuralı eklenir.
     */
    public static function registerSqliteFunctions(PDO $pdo): void
    {
        // PHP 8.4+ SQLite bağlantısı için Pdo\Sqlite sınıfını döndürür
        if ($pdo instanceof Sqlite) {
            $pdo->createFunction('tr_lower', self::lower(...), 1);
            $pdo->createCollation('turkish', self::compare(...));

            return;
        }

        $pdo->sqliteCreateFunction('tr_lower', self::lower(...), 1);
        $pdo->sqliteCreateCollation('turkish', self::compare(...));
    }
}
