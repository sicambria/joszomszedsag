<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lookup table replacing the Voyager `roles` BREAD (seeded in DatabaseSeeder).
 * Ids are legacy: 1 = admin, 6 = volunteer, 7 = requester (see RoleIds).
 */
class Role extends Model
{
    protected $fillable = ['name', 'display_name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
