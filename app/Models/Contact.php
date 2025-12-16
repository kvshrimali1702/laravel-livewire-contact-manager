<?php

namespace App\Models;

use App\Enums\GenderOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'profile_image',
        'additional_file',
        'master_id',
    ];

    /**
     * Cast attributes.
     * Casting `gender` to the enum ensures we work with `GenderOptions` instances.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'gender' => GenderOptions::class,
    ];

    /**
     * Contact has many custom fields.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(ContactCustomField::class);
    }

    /**
     * Secondary contacts that belong to this master contact.
     */
    public function secondaryContacts(): HasMany
    {
        return $this->hasMany(self::class, 'master_id');
    }

    /**
     * Parent (master) contact for a secondary contact.
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(self::class, 'master_id');
    }
}
