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
}
