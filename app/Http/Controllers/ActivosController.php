<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activo;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;

class ActivosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        AuthController::validateCredentials($request);
        $query = null;

        /** Mostrar valor guardado en auditoria actual */
        $auditoria_actual = $request->input('auditoria_actual') ? $request->input('auditoria_actual') : '0';
        /** Paginado */
        $page_size = $request->input('page_size') ? $request->input('page_size') : self::$PAGE_SIZE_DEFAULT;
        $page = $request->input('page') ? $request->input('page') : 1;
        /** Filtros */
        $existencia = $request->input('existencia');
        if (($existencia === '0' || $existencia === 'false') && !is_null($existencia)) {
            $existencia = -1;
        } // Se pone en -1 porque 0 se toma como null en el when
        /** Busqueda */
        $search = $request->input('search');
        /** Ordenamiento */
        $sort_by = $request->input('sort_by') ? $request->input('sort_by') : 'idActivoFijo';
        $sort_order = $request->input('sort_order') ? $request->input('sort_order') : 'asc';

        /** Verifiacion de existencia de los campos obligatorios */
        if (is_null($auditoria_actual) || $auditoria_actual == "0") {
            return self::warningNoParameters();
        }
        /** Validar que la auditoria_actual proporcionada existe */
        $auditoria_actual_obj = Auditoria::find($auditoria_actual);
        if (is_null($auditoria_actual_obj)) {
            $auditoria_max_id = Auditoria::all()->max('idAuditoria');
            return self::warningNoExisteAuditoriaParaId($auditoria_actual, $auditoria_max_id);
        }


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
                                        
            ->when($auditoria_actual, function($ifwhere) use ($auditoria_actual_obj) {
                return $ifwhere->where('EMP.idEmpresa', $auditoria_actual_obj->idEmpresa)
                ->where('DEP.idDepartamento', $auditoria_actual_obj->idDepartamento)
                ->where('ACT.idClasificacion', $auditoria_actual_obj->idClasificacion);
            })
            ->when($search, function ($ifwhere) use ($search) {
                return $ifwhere->where('ACT.descripcion', 'like', '%' . $search . '%');
            })
            ->when($existencia, function ($ifwhere) use ($existencia) {
                if ($existencia == -1) {
                    return $ifwhere->whereNull('AUA.existencia');
                } else {
                    return $ifwhere->whereNotNull('AUA.existencia')->whereNotNull('AU.fechaGuardada');
                }
            })


            ->orderBy($sort_by, $sort_order)
            ->skip(($page - 1) * $page_size)
            ->take($page_size)
            ->get();


        /** Filtrado de los registros nulos y retirado de los existencias  de auditorias no guardadas */
        $query = $query->filter(function ($registro) {
            if (is_null($registro->existencia_guardada) || is_null($registro->fecha_existencia)) {
                unset($registro->existencia_guardada);
                unset($registro->fecha_existencia);
                unset($registro->id_auditoria_existencia);
                unset($registro->auditoria_autor);
            }

            if (is_null($registro->existencia_actual)) {
                unset($registro->existencia_actual);
            }

            return true;
        });


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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        AuthController::validateCredentials($request);
        $query = DB::table('movimiento_detalle AS MVD')
            ->join('activosfijos AS ACT', 'MVD.idActivoFijo', 'ACT.idActivoFijo')
            ->join('movimientos AS MV', 'MVD.idMovimiento', 'MV.idMovimiento')
            ->join('departamentos AS DEP', 'MV.destino', 'DEP.idDepartamento')
            ->join('empresas AS EMP', 'DEP.idEmpresa', 'EMP.idEmpresa')
            ->join('clasificaciones as CLAS', 'ACT.idClasificacion', 'CLAS.idClasificacion')
            ->leftJoin('auditorias_activofijos as AUA', 'MVD.idActivoFijo', 'AUA.idActivoFijo')
            ->leftJoin('auditorias as AU', 'AUA.idAuditoria', 'AU.idAuditoria')
            ->select(
                'MVD.idActivoFijo',
                'ACT.descripcion',
                DB::raw('CONVERT(AUA.existencia, SIGNED) AS existencia_guardada'),
                'AU.fechaGuardada AS fecha_existencia',
                'AUA.idAuditoria as id_auditoria_existencia',
                'AU.idUser AS auditoria_autor',
                'EMP.idEmpresa',
                'DEP.idDepartamento',
                'ACT.idClasificacion',
                'EMP.nombre as empresa',
                'DEP.nombre as departamento',
                'CLAS.nombre as clasificacion',
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
                    ELSE 1=1
                END'
            )
            ->where('MVD.idActivoFijo', $id)                            
            ->get();

            if(!isset($query[0])) {
                $query = Activo::select()
                ->get()
                ->find($id);
                return self::queryOk($query);
            }


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
    public function update(Request $request, $id)
    {
        //
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

    /* funcion para obtener la ubicacion del activo a partir de su id
    PARAMS: idActivoFijo
    RETURN: idDepartamento
    */
    private function getUbicacion($idActivoFijo)
    {
        /*
        SELECT
            MV.destino AS 'idDepartamento'
        FROM movimientos MV
        JOIN movimiento_detalle MVD
            ON MV.idMovimiento = MVD.idMovimiento
        WHERE MVD.idActivoFijo = 117
        ORDER BY MV.fecha_acepta desc
        LIMIT 1;
        */

        return DB::table('movimientos')
            ->join('movimiento_detalle', 'movimientos.idMovimiento', '=', 'movimiento_detalle.idMovimiento')
            ->select('*')
            ->where('movimiento_detalle.idActivoFijo', $idActivoFijo)
            ->orderBy('fecha_acepta', 'desc')
            ->take(1)
            ->get();
    }
}
