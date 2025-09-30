<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebAuthnCredential extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'webauthn_credentials';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'credential_id',
        'public_key',
        'sign_count',
        'transports',
        'last_used_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'transports' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
