<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactCustomField extends Model
{
    use HasFactory;

    protected $table = 'contact_custom_fields';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'contact_id',
        'field_name',
        'field_value',
        'is_searchable',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'is_searchable' => 'boolean',
    ];

    /**
     * Custom field belongs to a contact.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
