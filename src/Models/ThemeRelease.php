<?php

namespace Webuild\ThemeKit\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeRelease extends Model
{
    protected $table = 'theme_releases';

    protected $guarded = [];

    protected $casts = [
        'manifest_snapshot' => 'array',
        'released_at' => 'datetime',
    ];
}

