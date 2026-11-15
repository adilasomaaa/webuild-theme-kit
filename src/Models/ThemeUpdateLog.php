<?php

namespace Webuild\ThemeKit\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeUpdateLog extends Model
{
    protected $table = 'theme_update_logs';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}

