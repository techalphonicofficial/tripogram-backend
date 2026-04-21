<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class PackageAddonSchemas extends Model
{
    protected $fillable = ['package_id', 'schema_type', 'schema'];

    public function package()
    {
        return $this->belongsTo(Packages::class, 'package_id');
    }
}
