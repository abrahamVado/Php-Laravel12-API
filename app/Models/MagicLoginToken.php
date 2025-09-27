<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @use HasFactory<\Database\Factories\MagicLoginTokenFactory> */
class MagicLoginToken extends Model
{
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'ip',
        'user_agent',
        'used_ip',
        'used_ua',
        'remember',
        'redirect_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'remember' => 'boolean',
    ];

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
