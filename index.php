<?php
//var_dump($_POST);
//var_dump($_GET);

require_once 'vendor/autoload.php';
require_once 'activosfijos.php';
use Illuminate\Database\Capsule\Manager as Capsule;
use Api\Models\ActivosFijos;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'grupodic_activofijo',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);
// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();
// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();


//$activos = ActivosFijos::where("cedoIndice", "=", "5")->get();
$activos = ActivosFijos::find(31);
echo $activos->toJson(JSON_UNESCAPED_UNICODE);

/*
$activo = new ActivosFijos();
$activo->cpaiIndice = 1;
$activo->cedoIndice = 40;
$activo->cedoNombre = "olvsijala";
$activo->save();
*/

?>