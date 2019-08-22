<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static $PAGE_SIZE_DEFAULT = 25;

    public static function status($status, $description) {
        return array(
            'status' => $status,
            'description' => $description
        );
    }

    public static function queryOk($query)
    {
        $return = AuthController::status('ok', 'Query correcto');
        $return += ['list' => $query];
        return Response($return, 200);
    }

    public static function queryEmpty()
    {
        $return = AuthController::status('ok', 'Query correcto, pero sin resultados!');
        return Response($return, 200);
    }

    public static function loginSucess()
    {
        $return = AuthController::status('ok', 'Inicio de sesión correcto!');
        return Response($return, 200);
    }

    public static function querySaved($type = 'create')
    {
        $message = 'created';

        if($type == 'update') {
            $message = 'updated';
        }
        
        $return = AuthController::status('ok', 'Entry sucessfuly '.$message);

        return Response($return, 200);
    }

    public static function warningNoParameters()
    {
        $return = AuthController::status('warning', 'Can not create new entry, more parameters requiered!');
        return Response($return, 400);
    }

    public static function warningNoSaved()
    {
        $return = AuthController::status('warning', 'Can not save!');
        return Response($return, 400);
    }

    public static function warningEntryNoExist()
    {
        $return = AuthController::status('warning', 'Can not get the specified entry!');
        return Response($return, 400);
    }

    public static function warningAuditoriaGuardada()
    {
        $return = AuthController::status('warning', 'No se puede editar una auditoria marcada como guardada.');
        return Response($return, 400);
    }

    public static $msgAuditoriaTerminadaRegistro = 'No se pueden agregar activos a una auditoria marcada como terminada.';
    public static $msgAuditoriaGuardada = 'No se puede editar una auditoria marcada como guardada.';

    public static function warningAuditoriaNoTerminada()
    {
        $return = AuthController::status('warning', 'No se puede marcar como guardada una auditoria no terminada.');
        return Response($return, 400);
    }
    
    public static function warningNoChanges()
    {
        $return = AuthController::status('warning', 'Sin cambios!');
        return Response($return, 400);
    }

    public static function errorExit($msg) {
        exit(response()->json(self::status('error', $msg))->content());
    }
}
