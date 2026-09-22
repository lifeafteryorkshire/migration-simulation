<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLegacyAddress extends Model
{
    protected $table = 'user_legacy_addresses';

    protected $fillable = [
        'user_id',
        'address_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function legacyAddress()
    {
        return $this->belongsTo(LegacyAddress::class, 'address_id', 'AddressID');
    }
}
