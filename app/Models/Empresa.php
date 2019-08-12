<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';
    public $timestamps = false;
    protected $primaryKey = "idEmpresa";
    protected $fillable = []; //hace que la tabla no se pueda actualizar
}
