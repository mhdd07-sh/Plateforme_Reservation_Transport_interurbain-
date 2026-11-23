<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal des actions de l'administrateur (validation, suspension, désactivation...).
 */
class JournalAdmin extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'journal_admin';

    protected $fillable = [
        'user_id',
        'action',
        'cible_type',
        'cible_id',
        'libelle',
    ];

    public static function enregistrer(string $action, string $libelle, ?Model $cible = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'cible_type' => $cible ? class_basename($cible) : null,
            'cible_id' => $cible?->getKey(),
            'libelle' => $libelle,
        ]);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}