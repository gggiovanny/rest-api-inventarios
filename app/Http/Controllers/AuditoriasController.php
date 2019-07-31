<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;

class AuditoriasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       AuthController::validateCredentials($request);

       /** Paginado */
       $page_size = $request->input('page_size') ? $request->input('page_size') : 25;
       $page = $request->input('page') ? $request->input('page') : 1;
       /** Filtros */
       $user = $request->input('user');
       /** Filtrar por el estatus de la auditoria, donde:
        * 0: cualquier status.
        * 1: en curso.
        * 2: terminada.
        * 3: guardada (solo entonces se puede considerar como auditoria base).
        */
       $status = $request->input('status');
       /** Busqueda */
       $search = $request->input('search');
       /** Ordenamiento */
       $sort_by = $request->input('sort_by') ? $request->input('sort_by') : 'idAuditoria';
       $sort_order = $request->input('sort_order') ? $request->input('sort_order') : 'asc';


       $query = Auditoria::when($user, function($ifwhere) use ($user) {
                    return $ifwhere->where('idUser', $user); })

                    ->when($terminada, function($ifwhere) use ($terminada) {
                        return $ifwhere->where('terminada', $terminada); })
                    ->when($guardada, function($ifwhere) {
                        return $ifwhere->whereNotNull('fechaGuardada'); })

                    ->when($status, function($filterstatus) use ($status){
                        switch ($status) {
                            case 1:
                                return $filterstatus->where('terminada', false)
                                        ->whereNull('fechaGuardada');
                                break;
                            case 2:
                                return $filterstatus->where('terminada', true)
                                        ->whereNull('fechaGuardada');
                                break;
                            case 3:
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
                        ->get()
                    ;
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
        $idUser = $request->input('user');
        $descripcion = $request->input('descripcion');

        /** Comprobacion de que se cumplen los parametros */
        if(!($descripcion && $idUser)) {
            return self::warningNoParameters();
        }

        $newAuditoria = new Auditoria;

        $newAuditoria->idUser = $idUser;
        $newAuditoria->descripcion = $descripcion;

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
//        Auditoria::find

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

        /** Verificacion de que existe la auditoria solicitada */
        $editAuditoria = Auditoria::find($id);
        if($editAuditoria === null) {
            return self::warningEntryNoExist();
        }

        /** Verifiacion de existencia de los campos que se actualizaran */
        if(!($terminada || $fechaGuardada)) {
            return self::warningNoParameters();
        }

        /** Actualizacion de solo los campos especificados por los parametros proporcionados */
        if($terminada) {
            $editAuditoria->terminada = 1;
        }
        if($fechaGuardada) {
            $editAuditoria->fechaGuardada = DB::raw('now()');
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
