<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedFilter extends Model
{
    protected $fillable = ['name', 'description', 'table_type', 'filters'];

    protected $casts = [
        'filters' => 'array',
    ];
}
