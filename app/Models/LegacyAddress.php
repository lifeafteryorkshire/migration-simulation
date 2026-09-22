<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LegacyAddress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'legacy_addresses';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'AddressID';

    /**
     * Indicates if the IDs are auto-incrementing.
     * Set to false because we are preserving Access primary keys.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'AddressID',
        'AddressLine1',
        'AddressLine2',
        'TownCity',
        'County',
        'Postcode',
        'DateCreated',
        'Active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'AddressID'   => 'integer',
        'DateCreated' => 'datetime',
        'Active'      => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Data Hygiene & Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Regex for validating standard UK Postcodes.
     */
    public const POSTCODE_REGEX = '/^(GIR 0AA|(?:(?:[A-Z][0-9]{1,2})|(?:(?:[A-Z][A-HJ-Y][0-9]{1,2})|(?:(?:[A-Z][0-9][A-Z])|(?:[A-Z][A-HJ-Y][0-9][A-Z]))))\s?[0-9][A-Z]{2})$/i';

    /**
     * Check if the address record has obvious corruption/typos.
     *
     * @return bool
     */
    public function getIsCorruptedAttribute(): bool
    {
        // Missing or non-matching postcode
        if (!$this->Postcode || !preg_match(self::POSTCODE_REGEX, $this->Postcode)) {
            return true;
        }

        // Dummy test postcodes (e.g., ZZ99 9ZZ)
        if (str_starts_with($this->Postcode, 'ZZ')) {
            return true;
        }

        // Special character corruptions (like '??') in line 1
        if (preg_match('/[^\w\s\-\,\.\']/i', $this->AddressLine1)) {
            return true;
        }

        // Digits inside town names (e.g., Manc3ster)
        if (preg_match('/\d/', $this->TownCity)) {
            return true;
        }

        return false;
    }

    /**
     * Scope to return only clean records.
     */
    public function scopeClean($query)
    {
        return $query->where('TownCity', 'NOT REGEXP', '[0-9]')
                     ->where('AddressLine1', 'NOT REGEXP', '[^a-zA-Z0-9 ]')
                     ->where('Postcode', 'REGEXP', '^[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][A-Z]{2}$');
    }
}
