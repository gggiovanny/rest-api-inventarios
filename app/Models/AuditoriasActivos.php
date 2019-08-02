<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaActivo extends Model
{
    protected $table = 'auditorias_activofijos';
    public $timestamps = false;
    protected $primaryKey = "idAudRegistro";
    protected $protected = ['idAudRegistro'];
}
