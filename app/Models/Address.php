<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_address_id',
        'uprn',
        'address_line_1',
        'address_line_2',
        'town_city',
        'county',
        'postcode',
        'latitude',
        'longitude',
        'easting',
        'northing',
        'is_active',
    ];

    protected $casts = [
        'legacy_address_id' => 'integer',
        'latitude'          => 'float',
        'longitude'         => 'float',
        'easting'           => 'integer',
        'northing'          => 'integer',
        'is_active'         => 'boolean',
    ];

    /**
     * Scope to return only geocoded records.
     */
    public function scopeGeocoded($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }
}
