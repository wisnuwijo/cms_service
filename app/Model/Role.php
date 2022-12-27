<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;

class Role extends Model
{
    use Uuids;
    
    protected $fillable = ["*"];
}
