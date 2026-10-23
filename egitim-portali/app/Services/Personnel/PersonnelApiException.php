<?php

namespace App\Services\Personnel;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

/**
 * Personel API'si ile iletişimde oluşan hatalar. Teknik mesaj log'a yazılır;
 * kullanıcıya yalnızca USER_MESSAGE gösterilir.
 */
class PersonnelApiException extends RuntimeException
{
    public const USER_MESSAGE = 'Personel bilgileri şu anda alınamıyor. Lütfen biraz sonra tekrar deneyin.';

    public static function missingToken(): self
    {
        return new self('Personel API token\'ı tanımlı değil. .env dosyasında PERSONNEL_API_TOKEN değerini ayarlayın.');
    }

    public static function unreachable(Throwable $previous): self
    {
        return new self('Personel Sistemi\'ne ulaşılamıyor. Servisin çalıştığından emin olun.', 0, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        $message = match ($response->status()) {
            401 => 'Personel API token\'ı geçersiz, iptal edilmiş ya da süresi dolmuş.',
            403 => 'Personel API token\'ının bu işlem için yetkisi yok.',
            404 => 'İstenen kayıt Personel Sistemi\'nde bulunamadı.',
            429 => 'Personel API istek limiti aşıldı. Biraz sonra tekrar deneyin.',
            default => "Personel Sistemi beklenmeyen bir hata döndürdü (HTTP {$response->status()}).",
        };

        return new self($message, $response->status());
    }
}
