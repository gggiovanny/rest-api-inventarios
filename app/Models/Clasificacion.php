<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clasificacion extends Model
{
    protected $table = 'clasificaciones';
    public $timestamps = false;
    protected $primaryKey = "idClasificacion";
    protected $fillable = []; //hace que la tabla no se pueda actualizar
}
