<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditorias';
    public $timestamps = false;
    protected $primaryKey = "idAuditoria";
    protected $protected = ['idAuditoria', 'fechaCreacion'];
}