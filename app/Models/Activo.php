<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activo extends Model
{
    protected $table = 'activosfijos';
    public $timestamps = false;
    protected $primaryKey = "idActivoFijo";
    protected $fillable = []; //hace que la tabla no se pueda actualizar
}
