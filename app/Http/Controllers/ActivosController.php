<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activo;
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

        /** Mostrar activos sin ubicacion */
        $mostrarSinUbicacion = $request->input('sin_ubicacion') == 'false' ? null : $request->input('sin_ubicacion');
        /** Paginado */
        $page_size = $request->input('page_size') ? $request->input('page_size') : 25;
        $page = $request->input('page') ? $request->input('page') : 1;
        /** Filtros */         
        $idEmpresa = $request->input('empresa');
        $idDepartamento = $request->input('departamento');
        $idClasificacion = $request->input('clasificacion');
        $conteo = $request->input('conteo');
        if($conteo == 0 && !is_null($conteo)) { $conteo = -1; } // Se pone en -1 porque 0 se toma como null en el when
        /** Busqueda */
        $search = $request->input('search');
        /** Ordenamiento */
        $sort_by = $request->input('sort_by') ? $request->input('sort_by') : 'idActivoFijo';
        $sort_order = $request->input('sort_order') ? $request->input('sort_order') : 'asc';

        if($mostrarSinUbicacion)
        {
            $query = Activo::leftJoin('movimiento_detalle AS MVD', 'activosfijos.idActivoFijo', 'MVD.idActivoFijo')
                        ->leftJoin('auditorias_activofijos as AUA', 'activosfijos.idActivoFijo', 'AUA.idActivoFijo')
                        ->leftJoin('auditorias as AU', 'AUA.idAuditoria', 'AU.idAuditoria')
                        ->select(   'activosfijos.idActivoFijo'
                                    ,'activosfijos.descripcion'
                                    ,'AUA.conteo'
                                    ,'AU.fechaGuardada AS fecha_conteo'
	                                ,'AUA.idAuditoria as id_auditoria_conteo'
                                    ,'activosfijos.idClasificacion'
                                )
                        ->whereNull('MVD.idMovimientoDetalle')
                        ->when($idClasificacion, function($ifwhere) use ($idClasificacion) {
                            return $ifwhere->where('ACT.idClasificacion', $idClasificacion); })
                        ->when($search, function($ifwhere) use ($search) {
                            return $ifwhere->where('activosfijos.descripcion', 'like', '%'.$search.'%'); })

                        ->orderBy($sort_by, $sort_order)
                        ->skip(($page-1)*$page_size)
                        ->take($page_size)
                        ->get()
                        ;
        }
        else
        {
            $query = DB::table('movimiento_detalle AS MVD')
                        ->join('activosfijos AS ACT', 'MVD.idActivoFijo', 'ACT.idActivoFijo')
                        ->join('movimientos AS MV', 'MVD.idMovimiento', 'MV.idMovimiento')
                        ->join('departamentos AS DEP', 'MV.destino', 'DEP.idDepartamento')
                        ->join('empresas AS EMP', 'DEP.idEmpresa', 'EMP.idEmpresa')
                        ->leftJoin('auditorias_activofijos as AUA', 'MVD.idActivoFijo', 'AUA.idActivoFijo')
                        ->leftJoin('auditorias as AU', 'AUA.idAuditoria', 'AU.idAuditoria')
                        ->select(   'MVD.idActivoFijo'
                                    ,'ACT.descripcion'
                                    ,'AUA.conteo'
                                    ,'AU.fechaGuardada AS fecha_conteo'
	                                ,'AUA.idAuditoria as id_auditoria_conteo'
                                    ,'ACT.idClasificacion'
                                    ,'DEP.idDepartamento'
                                    ,'EMP.idEmpresa'
                                    ,'MV.fecha_acepta AS ultimo_movimiento'	
                                )
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
                                ELSE AU.fechaGuardada is null								
                            END			
                        ) ')
                        ->when($idEmpresa, function($ifwhere) use ($idEmpresa) {
                            return $ifwhere->where('EMP.idEmpresa', $idEmpresa); })
                        ->when($idDepartamento, function($ifwhere) use ($idDepartamento) {
                            return $ifwhere->where('DEP.idDepartamento', $idDepartamento); })
                        ->when($idClasificacion, function($ifwhere) use ($idClasificacion) {
                            return $ifwhere->where('ACT.idClasificacion', $idClasificacion); })
                        ->when($search, function($ifwhere) use ($search) {
                            return $ifwhere->where('ACT.descripcion', 'like', '%'.$search.'%'); })
                        ->when($conteo, function($ifwhere) use ($conteo) {
                            if($conteo == -1) {
                                return $ifwhere->whereNull('AUA.conteo'); 
                            } else {
                                return $ifwhere->whereNotNull('AUA.conteo'); 
                            }
                        })
                        
                        ->orderBy($sort_by, $sort_order)
                        ->skip(($page-1)*$page_size)
                        ->take($page_size)
                        ->get()
                        ;
        }

        $filtered = $query->filter(function ($registro) {
            if(!$registro->conteo) {
                unset($registro->conteo);
                unset($registro->fecha_conteo);
                unset($registro->id_auditoria_conteo);
            }
            
            /*
            foreach ($registro as &$column) {
                if(!$column) {
                    unset($column);
                }
            }
            */
            return true;
        });
        return $filtered;
        

       // return self::queryOk($query);
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
        return self::queryOk(Activo::find($id));
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
