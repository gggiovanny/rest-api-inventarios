<?php
use App\Models\Activo;
namespace App;

$xd = DB::connection('mysql')->select('select * from empresas');
var_dump($xd);
