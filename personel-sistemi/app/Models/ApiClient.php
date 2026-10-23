<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * Personel API'sini kullanan dış sistem (ör. Eğitim Portalı).
 *
 * Token'lar Sanctum'un personal_access_tokens tablosunda SHA-256 hash'i
 * olarak saklanır; düz metin token yalnızca oluşturulduğu anda gösterilir.
 */
#[Fillable(['name', 'description'])]
class ApiClient extends Model
{
    use HasApiTokens;
}
