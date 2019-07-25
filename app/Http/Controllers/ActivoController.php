<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB as bd2;



class ActivoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {        
        $idEmpresa = $request->input('empresa');
        $idDepartamento = $request->input('departamento');

        return DB::table('movimiento_detalle AS MVD')
                ->join('activosfijos AS ACT', 'MVD.idActivoFijo', 'ACT.idActivoFijo')
                ->join('movimientos AS MV', 'MVD.idMovimiento', 'MV.idMovimiento')
                ->join('departamentos AS DEP', 'MV.destino', 'DEP.idDepartamento')
                ->join('empresas AS EMP', 'DEP.idEmpresa', 'EMP.idEmpresa')
                ->select(   'MVD.idActivoFijo',
                            'ACT.descripcion',
                            'EMP.nombre',
                            'DEP.nombre')
                     /*->where('MV.fecha_acepta', '=', function($subq)
                    {
                        return $subq->select(DB::raw('max(m.fecha_acepta)'))
                        ->from('movimientos as m')
                        ->join('movimiento_detalle as md', 'm.idMovimiento', 'md.idMovimiento')
                        ->where('md.idActivoFijo', 'MVD.idActivoFijo');
                    })*/
                ->whereRaw('MV.fecha_acepta =	(
                    select MAX(m.fecha_acepta)
                    from movimientos m
                    inner join movimiento_detalle md
                        on m.idMovimiento = md.idMovimiento
                    where md.idActivoFijo = MVD.idActivoFijo
                    )') 
                ->when($idEmpresa, function($ifwhere) use ($idEmpresa) {
                    return $ifwhere->where('EMP.idEmpresa', $idEmpresa); })
                ->when($idDepartamento, function($ifwhere) use ($idDepartamento) {
                    return $ifwhere->where('DEP.idDepartamento', $idDepartamento); })
                    

                
                ->orderBy('idActivoFijo', 'asc')
                ->get()
                ;
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
    public function show($id)
    {
        return Activo::find($id);
        //return $this->getUbicacion($id);
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
