<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditoriasActivos;
use App\Models\Auditoria;
use App\Models\Activo;
use Illuminate\Support\Facades\DB;

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
        $existencia = $request->input('existencia');

        if(is_null($id_activo) && is_null($existencia)) {
            return self::warningNoParameters();
        }

        if(self::alreadyExists($id_activo, $idAuditoria)) {
            return $this->update($idAuditoria, $id_activo, $request);
        }

        $existencia_anterior = self::getExistenciaAnteriorActual($id_auditoria, $id_activo);
        
        $new = new AuditoriasActivos();
        
        $new->idAuditoria = $idAuditoria;
        $new->idActivoFijo = $id_activo;
        $new->idUser = $idUser;
        if($existencia == 0 && !is_null($existencia)) {
            $new->existencia = 0;
        }
        if($existencia >= 1 || $existencia === 'true') {
            $new->existencia = 1;
        }
        $new->existencia_anterior = $existencia_anterior;
        

        if($new->save()) {
            return self::postOk();
        } else {
            return self::warningNoSaved();
        }
    }

    private static function getExistenciaAnteriorActual($auditoria_actual, $idActivo) {

        $query = DB::table('movimiento_detalle AS MVD')
        ->join('activosfijos AS ACT', 'MVD.idActivoFijo', 'ACT.idActivoFijo')
        ->join('movimientos AS MV', 'MVD.idMovimiento', 'MV.idMovimiento')
        ->join('departamentos AS DEP', 'MV.destino', 'DEP.idDepartamento')
        ->join('empresas AS EMP', 'DEP.idEmpresa', 'EMP.idEmpresa')
        ->leftJoin('auditorias_activofijos as AUA', 'MVD.idActivoFijo', 'AUA.idActivoFijo')
        ->leftJoin('auditorias as AU', 'AUA.idAuditoria', 'AU.idAuditoria')
        ->select(
            'MVD.idActivoFijo',
            'ACT.descripcion',
            DB::raw('CASE WHEN AU.fechaGuardada IS NULL THEN NULL ELSE CONVERT(AUA.existencia, SIGNED) END AS existencia_guardada'),
            DB::raw('(select existencia from auditorias_activofijos where idAuditoria = ' . $auditoria_actual . ' and idActivoFijo = MVD.idActivoFijo) as "existencia_actual"'),
            'AU.fechaGuardada AS fecha_existencia',
            'AUA.idAuditoria as id_auditoria_existencia',
            'AU.idUser AS auditoria_autor',
            'ACT.idClasificacion',
            'DEP.idDepartamento',
            'EMP.idEmpresa',
            'MV.fecha_acepta AS ultimo_movimiento'
        )
        ->where('ACT.estatus', 'false')
        ->whereRaw('MV.fecha_acepta =	(
                    select MAX(m.fecha_acepta)
                    from movimientos m
                    inner join movimiento_detalle md
                        on m.idMovimiento = md.idMovimiento
                    where md.idActivoFijo = MVD.idActivoFijo
                    )')
        ->whereRaw(
            ' -- NOTA: Se recorren todos los activos fijos de auditorias, por ello sin los CASE se verian identificadores repetidos de activo fijo.
            CASE
                -- CUANDO EL ACTIVO FIJO DE AUDITORIA ESTA GUARDADO, SOLO MOSTRAR EL MAS RECIENTE (los demas no pasan el filtro y no se visualizan).
                WHEN (	select count(*)
                        from auditorias_activofijos aa
                        inner join auditorias a
                            on aa.idAuditoria = a.idAuditoria
                        where a.fechaGuardada is not null
                        and aa.idActivoFijo = MVD.idActivoFijo ) >= 1
                THEN AU.fechaGuardada =	(
                                        select MAX(a.fechaGuardada)
                                        from auditorias_activofijos aa
                                        inner join auditorias a
                                            on aa.idAuditoria = a.idAuditoria
                                        where aa.idActivoFijo = MVD.idActivoFijo
                                        )
                ELSE 
                    CASE
                        -- SI el activo fijo pertenece a alguna auditoria y NO esta guardado, solo traer 1, por lo que...
                            -- SI hay alguno de la auditoria actual, mostrar solo ese
                            -- SI NO, mostrar el mas reciente y ocultar su existencia, guardado y autor (ese ocultamiento se hace en el SELECT).
                        -- SI NO esta en ninguna auditoria, mostrarlo tal cual.
                        WHEN AUA.idAuditoria IS NOT NULL -- SI ES NULL, ENTONCES DICHO ACTIVO NO ESTA EN UNA AUDITORIA
                            AND AU.fechaGuardada IS NULL
                        THEN	
                            case
                                -- este select regresa el numero de activos fijos de auditorias no guardados que pertenezcan a la auditoria actual
                                when (	select count(*)
                                        from auditorias_activofijos aa
                                        inner join auditorias a
                                            on aa.idAuditoria = a.idAuditoria
                                        where a.fechaGuardada is null
                                        and aa.idActivoFijo = MVD.idActivoFijo
                                        and aa.idAuditoria = ' . $auditoria_actual . ') >= 1
                                then AUA.idAuditoria = ' . $auditoria_actual . '
                                else AUA.idAuditoria = 	(
                                                            select MAX(aa.idAuditoria)
                                                            from auditorias_activofijos aa
                                                            inner join auditorias a
                                                                on aa.idAuditoria = a.idAuditoria
                                                            where a.fechaGuardada is null
                                                            and aa.idActivoFijo = MVD.idActivoFijo
                                                            and aa.idAuditoria <> ' . $auditoria_actual . '
                                                        )
                            end 
                        ELSE 1=1
                    END
            END'
        )
        ->where('MVD.idActivoFijo', $idActivo)
        ->get();

        if(isset($query[0])) {
            return $query[0]->existencia_guardada;
        } else {
            return null;
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
        $existencia = $request->input('existencia');

        if(is_null($existencia)) {
            return self::warningNoParameters();
        }

        $update = AuditoriasActivos::where('idActivofijo', $id_activo)->where('idAuditoria', $idAuditoria)->first();

        if(is_null($update)) {
            return self::warningEntryNoExist();
        }

        $update->idUser = $idUser;

        if($existencia == 0 && !is_null($existencia)) {
            $update->existencia = 0;
        }
        if($existencia >= 1 || $existencia === 'true') {
            $update->existencia = 1;
        }

        if($update->save()) {
            return self::putOK();
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
