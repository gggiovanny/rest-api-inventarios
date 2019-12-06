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
        if (self::isSaved($idAuditoria)) {
            self::errorExit(self::$msgAuditoriaGuardada);
        }
        if (self::isFinished($idAuditoria)) {
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
        if (!$auditoria) {
            return false;
        }

        if ($auditoria->fechaGuardada) {
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
        if (!$auditoria) {
            return false;
        }

        if (self::isTrue($auditoria->terminada)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checa si existe una auditoria sin guardar (es decir, en progreso) 
     * con la misma combinacion de empresa, departamento y clasificacion,
     * de tal manera que no haya duplicados en curso y ocurran conflictos.
     * Es recomendable en el lado del frontend sugerir trabajar sobre la auditoria
     * repetida si esta funcion devuelve un valor.
     * @return int // La id de la auditoria igual en progreso.
     */
    private static function getSameNotSavedId($idEmpresa, $idDepartamento, $idClasificacion) {
        if (
            is_null($idEmpresa) || $idEmpresa == "0" ||
            is_null($idDepartamento) || $idDepartamento == "0" ||
            is_null($idClasificacion) || $idClasificacion == "0"
        ) {
            return -1;
        }

        $auditoriaObj = Auditoria::where('idEmpresa', $idEmpresa)
                            ->where('idDepartamento', $idDepartamento)
                            ->where('idClasificacion', $idClasificacion)
                            ->whereNull('fechaGuardada')
                            ->first()
                            ;
        if(is_null($auditoriaObj)) {
            return -1;
        } else {
            return $auditoriaObj->idAuditoria;                      
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
        $sort_order = $request->input('sort_order') ? $request->input('sort_order') : 'desc';
        /** Filtrar por empresa, departamento y clasificacion */
        $idEmpresa = $request->input('empresa');
        $idDepartamento = $request->input('departamento');
        $idClasificacion = $request->input('clasificacion');

        $status_catalog = [
            1 => 'En curso',
            2 => 'Terminada',
            3 => 'Guardada',
            0 => 'Cualquiera'
        ];

        DB::statement("SET lc_time_names = 'es_MX';");
        $query = Auditoria::join('users as u', 'auditorias.idUser', 'u.id')
            ->leftJoin('empresas as e', 'auditorias.idEmpresa', 'e.idEmpresa')
            ->leftJoin('departamentos as d', 'auditorias.idDepartamento', 'd.idDepartamento')
            ->leftJoin('clasificaciones as c', 'auditorias.idClasificacion', 'c.idClasificacion')
            ->select(
                "idAuditoria as id",
                DB::raw("DATE_FORMAT(fechaCreacion, '%e de %M, %Y') as fechaCreacion"),
                DB::raw("(  CASE
                                        WHEN terminada = 0 AND fechaGuardada is null THEN '$status_catalog[1]'
                                        WHEN terminada = 1 AND fechaGuardada is null THEN '$status_catalog[2]'
                                        WHEN terminada = 1 AND fechaGuardada is not null THEN '$status_catalog[3]'
                                        ELSE '$status_catalog[0]'
                                    END
                        ) as status"),
                "auditorias.descripcion",
                "u.username",
                "auditorias.idEmpresa",
                "e.nombre as empresa",
                "auditorias.idDepartamento",
                "d.nombre as departamento",
                "auditorias.idClasificacion",
                "c.nombre as clasificacion",
                "terminada",
                "fechaGuardada"
            )

            ->when($user, function ($ifwhere) use ($user) {
                return $ifwhere->where('idUser', $user);
            })
            ->when($status, function ($filterstatus) use ($status, $status_catalog) {
                switch ($status) {
                    case 1:
                    case strtolower($status_catalog[1]):
                        return $filterstatus->where('terminada', false)
                            ->whereNull('fechaGuardada');
                        break;
                    case 2:
                    case strtolower($status_catalog[2]):
                        return $filterstatus->where('terminada', true)
                            ->whereNull('fechaGuardada');
                        break;
                    case 3:
                    case strtolower($status_catalog[3]):
                        return $filterstatus->whereNotNull('fechaGuardada');
                        break;
                    default:
                        break;
                }
            })
            ->when($idEmpresa, function ($ifwhere) use ($idEmpresa) {
                return $ifwhere->where('auditorias.idEmpresa', $idEmpresa);
            })
            ->when($idDepartamento, function ($ifwhere) use ($idDepartamento) {
                return $ifwhere->where('auditorias.idDepartamento', $idDepartamento);
            })
            ->when($idClasificacion, function ($ifwhere) use ($idClasificacion) {
                return $ifwhere->where('auditorias.idClasificacion', $idClasificacion);
            })
            



            ->when($search, function ($ifwhere) use ($search) {
                return $ifwhere->where('descripcion', 'like', '%' . $search . '%');
            })
            ->orderBy($sort_by, $sort_order)
            ->skip(($page - 1) * $page_size)
            ->take($page_size)
            ->get();

        /** Filtrado de los registros nulos */
        $query = $query->filter(function ($registro) {
            if (is_null($registro->idDepartamento)) {
                unset($registro->idDepartamento);
                unset($registro->departamento);
            }
            if (is_null($registro->idEmpresa)) {
                unset($registro->idEmpresa);
                unset($registro->empresa);
            }
            if (is_null($registro->idClasificacion)) {
                unset($registro->idClasificacion);
                unset($registro->clasificacion);
            }
            if (is_null($registro->fechaGuardada)) {
                unset($registro->fechaGuardada);
            }
            if (is_null($registro->descripcion)) {
                unset($registro->descripcion);
            }

            $registro->terminada = self::getCorrectBooleanStr($registro->terminada);

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
        AuthController::validateCredentials($request);

        /** Parametros necesarios para crear un nuevo registro */
        $idUser = AuthController::getUserFromToken($request->input('token'));

        $descripcion = $request->input('descripcion');
        $idEmpresa = $request->input('empresa');
        $idDepartamento = $request->input('departamento');
        $idClasificacion = $request->input('clasificacion');

        /** Verifiacion de existencia de los campos obligatorios */
        if (
            is_null($idEmpresa) || $idEmpresa == "0" ||
            is_null($idDepartamento) || $idDepartamento == "0" ||
            is_null($idClasificacion) || $idClasificacion == "0"
        ) {
            return self::warningNoParameters();
        }

        
        if($idSameAuditoria = self::getSameNotSavedId($idEmpresa, $idDepartamento, $idClasificacion) > 0) {
            return self::warningSameAuditoriaInProgress($idSameAuditoria);
        }

        $newAuditoria = new Auditoria;

        $newAuditoria->idUser = $idUser;
        $newAuditoria->descripcion = $descripcion ? $descripcion : null;
        $newAuditoria->idEmpresa = $idEmpresa ? $idEmpresa : null;
        $newAuditoria->idDepartamento = $idDepartamento ? $idDepartamento : null;
        $newAuditoria->idClasificacion = $idClasificacion ? $idClasificacion : null;


        if ($newAuditoria->save()) {
            $query = DB::select(DB::raw("select LAST_INSERT_ID() as id"));
            return self::postIdOk($query);
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
        if ($query) {
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
        if ($editAuditoria === null) {
            return self::warningEntryNoExist();
        }

        /** Si la auditoria ya fue marcada como guardada, no se permite su edicion */
        if (self::isSaved($id)) {
            return self::warningAuditoriaGuardada();
        }

        /** Verifiacion de existencia de los campos que se actualizaran */
        if (is_null($terminada) && is_null($fechaGuardada) && is_null($descripcion)) {
            return self::warningNoParameters();
        }

        $estaMarcadaComoTerminada = self::isFinished($id);

        /** Actualizacion de solo los campos especificados por los parametros proporcionados */
        if ($terminada == 0 && !is_null($terminada)) {
            $editAuditoria->terminada = 0;
            $estaMarcadaComoTerminada = false;
        }
        if ($terminada >= 1 || $terminada === 'true') {
            if(self::activosTienenEstatusValido($id)) {
                $editAuditoria->terminada = 1;
                $estaMarcadaComoTerminada = true;
            } else {
                return self::warningAuditoriaActivosNoCompletados();
            }
        }

        $estaMarcadaComoGuardada = false;
        if (!is_null($fechaGuardada) && $fechaGuardada) {
            if (!is_null($estaMarcadaComoTerminada) && $estaMarcadaComoTerminada) {
                $editAuditoria->fechaGuardada = DB::raw('now()');
                $estaMarcadaComoGuardada = true;
            } else {
                return self::warningAuditoriaNoTerminada();
            }
        }

        if (!is_null($descripcion) && $descripcion) {
            $editAuditoria->descripcion = $descripcion;
        }

        /** Guardado con comprobacion de éxito */
        if ($editAuditoria->save()) {

            $nuevoStatus = 'En curso';
            if($estaMarcadaComoTerminada) {
                $nuevoStatus = 'Terminada';
            }
            if( $estaMarcadaComoGuardada) {
                $nuevoStatus = 'Guardada';
            }
            return self::putOkPayload($nuevoStatus);
        } else {
            return self::warningNoSaved();
        }
    }

    /**
     * Valida que una auditoria tenga todos sus activos 
     * con algun estatus valido (encontrado o no encontrado).
     */
    static private function activosTienenEstatusValido($idAuditoria) {
        return self::getCountAuditoriaActivosValidados($idAuditoria) >= self::getCountAuditoriaActivosAll($idAuditoria);
    }

    /**
     * Regresa el conteo de activos con algun estatus de existencia a seteado
     */
    static private function getCountAuditoriaActivosValidados($idAuditoria) {
        $sql = DB::select(DB::raw("
        select count(*) as auditoria_activos_validados
        from auditorias_activofijos
        where idAuditoria = ".$idAuditoria."
        "));      
        return $sql[0]->auditoria_activos_validados;
    }

    /**
     * Regresa el conteo de activos de la auditoria
     */
    static private function getCountAuditoriaActivosAll($idAuditoria) {
        $sql = DB::select(DB::raw("
                    SELECT
                                count(MVD.idActivoFijo) as auditoria_activos
                            FROM movimiento_detalle MVD
                            INNER JOIN activosfijos ACT
                                ON MVD.idActivoFijo = ACT.idActivoFijo
                            INNER JOIN movimientos MV
                                ON MVD.idMovimiento = MV.idMovimiento
                            INNER JOIN departamentos DEP
                                ON MV.destino = DEP.idDepartamento
                            INNER JOIN empresas EMP
                                ON DEP.idEmpresa = EMP.idEmpresa
                            LEFT JOIN auditorias_activofijos AUA
                                ON MVD.idActivoFijo = AUA.idActivoFijo
                            LEFT JOIN auditorias AU
                                ON AUA.idAuditoria = AU.idAuditoria
                            WHERE ACT.estatus = 0
                            AND MV.fecha_acepta =	(
                                                        select MAX(m.fecha_acepta)
                                                        from movimientos m
                                                        inner join movimiento_detalle md
                                                            on m.idMovimiento = md.idMovimiento
                                                        where md.idActivoFijo = MVD.idActivoFijo
                                                    )
                            AND (-- NOTA: Se recorren todos los activos fijos de auditorias, por ello sin los CASE se verian identificadores repetidos de activo fijo.
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
                                                                and aa.idAuditoria = ".$idAuditoria.") >= 1
                                                        then AUA.idAuditoria = ".$idAuditoria."
                                                        else AUA.idAuditoria = 	(
                                                                                    select MAX(aa.idAuditoria)
                                                                                    from auditorias_activofijos aa
                                                                                    inner join auditorias a
                                                                                        on aa.idAuditoria = a.idAuditoria
                                                                                    where a.fechaGuardada is null
                                                                                    and aa.idActivoFijo = MVD.idActivoFijo
                                                                                    and aa.idAuditoria <> ".$idAuditoria."
                                                                                )
                                                    end 
                                                ELSE 1=1
                                            END
                                    END			
                                )
        
                            AND DEP.idDepartamento = (select au.idDepartamento from auditorias au where au.idAuditoria = ".$idAuditoria.")
                            AND EMP.idEmpresa = (select au.idEmpresa from auditorias au where au.idAuditoria = ".$idAuditoria.")
                            AND ACT.idClasificacion = (select au.idClasificacion from auditorias au where au.idAuditoria = ".$idAuditoria.")
        "));

        return $sql[0]->auditoria_activos;
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
