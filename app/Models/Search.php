<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Search extends Model
{
    use HasFactory;

    protected $appends = ['search'];

    public function getSearchAttribute()
    {
        return request()->get('search');
    }

}
