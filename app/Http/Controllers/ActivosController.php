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
        $idEmpresa = $request->input('empresa');
        $idDepartamento = $request->input('departamento');
        $idClasificacion = $request->input('clasificacion');
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
        if( is_null($auditoria_actual) || $auditoria_actual == "0") {
            return self::warningNoParameters();
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
            ->whereRaw('(
                        CASE
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
                            ELSE    CASE
                                        WHEN AUA.idAuditoria IS NOT NULL
                                        THEN AUA.idAuditoria = ' . $auditoria_actual . '
                                        ELSE 1=1
                                    END								
                        END			
                    ) ')
                    
            ->where('EMP.idEmpresa', Auditoria::find($auditoria_actual)->idEmpresa)
            ->where('DEP.idDepartamento', Auditoria::find($auditoria_actual)->idDepartamento)
            ->where('ACT.idClasificacion', Auditoria::find($auditoria_actual)->idClasificacion)
            
            ->when($idEmpresa, function ($ifwhere) use ($idEmpresa) {
                return $ifwhere->where('EMP.idEmpresa', $idEmpresa);
            })
            ->when($idDepartamento, function ($ifwhere) use ($idDepartamento) {
                return $ifwhere->where('DEP.idDepartamento', $idDepartamento);
            })
            ->when($idClasificacion, function ($ifwhere) use ($idClasificacion) {
                return $ifwhere->where('ACT.idClasificacion', $idClasificacion);
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
        $query = Activo::select()
            ->get()
            ->find($id);

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
