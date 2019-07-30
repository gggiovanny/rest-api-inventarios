<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function status($status, $description) {
        return array(
            'status' => $status,
            'description' => $description
        );
    }

    public static function queryOk($query)
    {
        $return = AuthController::status('ok', 'Query sucessful');
        $return += ['query' => $query];
        return Response($return, 200);
    }

    public static function queryEmpty()
    {
        $return = AuthController::status('ok', 'Query sucessful, but no results found!');
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

    

    
}
