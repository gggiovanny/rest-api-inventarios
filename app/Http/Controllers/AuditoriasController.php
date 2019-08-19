<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;

class AuditoriasController extends Controller
{
    /**
     * Interrumpe la peticion si la auditoria
     * está marcada como guardada o terminada
     */
    public static function validateAuditoriaStatus($idAuditoria)
    {
        if(self::isSaved($idAuditoria)) {
            self::errorExit(self::$msgAuditoriaGuardada);
        }
        if(self::isFinished($idAuditoria)) {
            self::errorExit(self::$msgAuditoriaTerminadaRegistro);
        }
    }


    /**
     * Checa si la auditoria con la id proporcionada
     * está marcada como guardada.
     * @return boolean
     */
    private static function isSaved($idAuditoria)
    {
        $auditoria = Auditoria::find($idAuditoria);
        if(!$auditoria) { return false;}
        
        if($auditoria->fechaGuardada) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checa si la auditoria con la id proporcionada
     * está marcada como terminada.
     * @return boolean
     */
    private static function isFinished($idAuditoria)
    {
        $auditoria = Auditoria::find($idAuditoria);
        if(!$auditoria) { return false;}

        if($auditoria->terminada == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       AuthController::validateCredentials($request);

       /** Paginado */
       $page_size = $request->input('page_size') ? $request->input('page_size') : self::$PAGE_SIZE_DEFAULT;
       $page = $request->input('page') ? $request->input('page') : 1;
       /** Filtros */
       $user = $request->input('user');
       /** Filtrar por el estatus de la auditoria, donde:
        * 0: cualquier status.
        * 1: en curso.
        * 2: terminada.
        * 3: guardada (solo entonces se puede considerar como auditoria base).
        */
       $status = strtolower($request->input('status'));
       /** Busqueda */
       $search = $request->input('search');
       /** Ordenamiento */
       $sort_by = $request->input('sort_by') ? $request->input('sort_by') : 'idAuditoria';
       $sort_order = $request->input('sort_order') ? $request->input('sort_order') : 'asc';

       $status_catalog = [
           1 => 'En curso',
           2 => 'Terminada',
           3 => 'Guardada',
           0 => 'Cualquiera'
       ];

       $query = Auditoria::join('users as u', 'auditorias.idUser', 'u.id')
                    ->select(
                        "idAuditoria as id",
                        "fechaCreacion",
                        DB::raw("(  CASE
                                        WHEN terminada = 0 AND fechaGuardada is null THEN '$status_catalog[1]'
                                        WHEN terminada = 1 AND fechaGuardada is null THEN '$status_catalog[2]'
                                        WHEN terminada = 1 AND fechaGuardada is not null THEN '$status_catalog[3]'
                                        ELSE '$status_catalog[0]'
                                    END
                        ) as status"),
                        "descripcion",
                        "username",
                        "terminada",
                        "fechaGuardada"
                    )

                    ->when($user, function($ifwhere) use ($user) {
                    return $ifwhere->where('idUser', $user); })
                    ->when($status, function($filterstatus) use ($status, $status_catalog){
                        switch ($status) {
                            case 1: case strtolower($status_catalog[1]):
                                return $filterstatus->where('terminada', false)
                                        ->whereNull('fechaGuardada');
                                break;
                            case 2: case strtolower($status_catalog[2]):
                                return $filterstatus->where('terminada', true)
                                        ->whereNull('fechaGuardada');
                                break;
                            case 3: case strtolower($status_catalog[3]):
                                return $filterstatus->whereNotNull('fechaGuardada');
                                break;
                            default:
                                break;
                        }
                    })
                    ->when($search, function($ifwhere) use ($search) {
                        return $ifwhere->where('descripcion', 'like', '%'.$search.'%');
                    })


                    ->orderBy($sort_by, $sort_order)
                        ->skip(($page-1)*$page_size)
                        ->take($page_size)
                        ->get();
        return self::queryOk($query);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        AuthController::validateCredentials($request);

        /** Parametros necesarios para crear un nuevo registro */
        $idUser = AuthController::getUserFromToken($request->input('token'));
        $descripcion = $request->input('descripcion');

        $newAuditoria = new Auditoria;

        $newAuditoria->idUser = $idUser;
        if($descripcion) {
            $newAuditoria->descripcion = $descripcion;
        }

        if($newAuditoria->save()) {
            return self::querySaved();
        } else {
            return self::warningNoSaved();
        }     
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        AuthController::validateCredentials($request);

        $query = Auditoria::find($id);
        if($query) {
            return self::queryOk($query);
        } else {
            return self::warningEntryNoExist();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        AuthController::validateCredentials($request);

        $terminada = $request->input('terminada');
        $fechaGuardada = $request->input('guardada');
        $descripcion = $request->input('descripcion');

        /** Verificacion de que existe la auditoria solicitada */
        $editAuditoria = Auditoria::find($id);
        if($editAuditoria === null) {
            return self::warningEntryNoExist();
        }

        /** Si la auditoria ya fue marcada como guardada, no se permite su edicion */
        if(self::isSaved($id)) {
            return self::warningAuditoriaGuardada();
        }

        /** Verifiacion de existencia de los campos que se actualizaran */
        if( is_null($terminada) && is_null($fechaGuardada && is_null($descripcion)) ) {
            return self::warningNoParameters();
        }
        
        $auditoriaTerminada = self::isFinished($id);

        /** Actualizacion de solo los campos especificados por los parametros proporcionados */
        if($terminada == 0 && !is_null($terminada)) {
            $editAuditoria->terminada = 0;
            $auditoriaTerminada = false;
        }
        if($terminada >= 1 || $terminada === 'true') {
            $editAuditoria->terminada = 1;
            $auditoriaTerminada = true;
        }
        
        if($fechaGuardada) {
            if($auditoriaTerminada) {
                $editAuditoria->fechaGuardada = DB::raw('now()');
            } else {
                return self::warningAuditoriaNoTerminada();
            }
        }

        if($descripcion) {
            $editAuditoria->descripcion = $descripcion;
        }

        /** Guardado con comprobacion de éxito */
        if($editAuditoria->save()) {
            return self::querySaved('update');
        } else {
            return self::warningNoSaved();
        }    
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
