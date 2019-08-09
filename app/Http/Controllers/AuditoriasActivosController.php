<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditoriasActivos;
use App\Models\Auditoria;
use App\Models\Activo;

class AuditoriasActivosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id_auditoria, Request $request)
    {
        AuthController::validateCredentials($request);
        /** Filtro para mostrar todas los registros auditorias indistintamente */
        $all = $request->input('all') == 'false' ? null : $request->input('all');
        /** Filtros */
        $user = $request->input('user');
        $activo = $request->input('activo');

        /** Paginado */
        $page_size = $request->input('page_size') ? $request->input('page_size') : self::$PAGE_SIZE_DEFAULT;
        $page = $request->input('page') ? $request->input('page') : 1;
        /** Ordenamiento */
        $sort_by = $request->input('sort_by') ? $request->input('sort_by') : 'idAuditoria';
        $sort_order = $request->input('sort_order') ? $request->input('sort_order') : 'asc';

        if($all) {
            $query = AuditoriasActivos::select()
                ->when($user, function($ifwhere) use ($user) {
                    return $ifwhere->where('idUser', $user); })
                ->when($activo, function($ifwhere) use ($activo) {
                    return $ifwhere->where('idActivoFijo', $activo); })

                ->orderBy($sort_by, $sort_order)
                        ->skip(($page-1)*$page_size)
                        ->take($page_size)
                        ->get();
        } else {
            $query = AuditoriasActivos::select()
                ->where('idAuditoria', $id_auditoria)
                ->when($user, function($ifwhere) use ($user) {
                    return $ifwhere->where('idUser', $user); })
                ->when($activo, function($ifwhere) use ($activo) {
                    return $ifwhere->where('idActivoFijo', $activo); })

                ->orderBy($sort_by, $sort_order)
                        ->skip(($page-1)*$page_size)
                        ->take($page_size)
                        ->get();
        }

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
     * Verifica si existe ya dicho activo para esa auditoria.
     *
     * @param $idActivoFijo
     * @param $idAuditoria
     * @return boolean
     */
    private static function alreadyExists($idActivoFijo, $idAuditoria)
    {
        if(is_null($audact = AuditoriasActivos::where('idActivofijo', $idActivoFijo)->where('idAuditoria', $idAuditoria)->first())) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($id_auditoria, $id_activo, Request $request)
    {
        AuthController::validateCredentials($request);
        
        /** Se verifica si existe la auditoria solicitada */
        $idAuditoria = Auditoria::find($id_auditoria) ? $id_auditoria : null;
        if(!$idAuditoria) { return self::warningEntryNoExist(); }
        /** Validacion de que no este guardada la auditoria a la que pertenece el activo */
        AuditoriasController::validateAuditoriaStatus($idAuditoria);
        /** Usuario obtenido a partir del token */
        $idUser = AuthController::getUserFromToken($request->input('token'));
        /** Verificacion de que existe el activo en cuestion */
        if(is_null(Activo::find($id_activo))) {
            return self::warningEntryNoExist();
        }
        /** Parametro obligatorios */
        $conteo = $request->input('conteo');

        if(!($id_activo && $conteo)) {
            return self::warningNoParameters();
        }

        if(self::alreadyExists($id_activo, $idAuditoria)) {
            return $this->update($idAuditoria, $id_activo, $request);
        }

        $new = new AuditoriasActivos();
        
        $new->idAuditoria = $idAuditoria;
        $new->idUser = $idUser;
        $new->idActivoFijo = $id_activo;
        $new->conteo = $conteo;

        if($new->save()) {
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
    public function show($id_auditoria, $id_activo, Request $request)
    {
        AuthController::validateCredentials($request);

        $query = AuditoriasActivos::select()
                ->where('idAuditoria', $id_auditoria)
                ->where('idActivoFijo', $id_activo)
                ->get();

        return self::queryOk($query);

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
    public function update($id_auditoria, $id_activo, Request $request)
    {
        AuthController::validateCredentials($request);

        /** Se verifica si existe la auditoria solicitada */
        $idAuditoria = Auditoria::find($id_auditoria) ? $id_auditoria : null;
        if(!$idAuditoria) { return self::warningEntryNoExist(); }
        /** Validacion de que no este guardada la auditoria a la que pertenece el activo */
        AuditoriasController::validateAuditoriaStatus($idAuditoria);
        /** Usuario obtenido a partir del token */
        $idUser = AuthController::getUserFromToken($request->input('token'));
        /** Parametro obligatorio */
        $conteo = $request->input('conteo');

        if(!$conteo) {
            return self::warningNoParameters();
        }


        $update = AuditoriasActivos::where('idActivofijo', $id_activo)->where('idAuditoria', $idAuditoria)->first();

        if(is_null($update)) {
            return self::warningEntryNoExist();
        }

        $update->idUser = $idUser;
        $update->conteo = $conteo;

        if($update->save()) {
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
