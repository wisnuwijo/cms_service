<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Uuids;

class ProductPhoto extends Model
{
    use Uuids;
    use SoftDeletes;
}
