# Rest API
Simple RESTful API for Android project

## Notas importantes
### Uso del token
Todas las peticiones a los endpoints requieren que se agregue como parametro un token de nombre 'token' válido y vigente (expira en 1 día). Para obtener un token, basta con hacer una peticion *GET* a *api/auth* usando como parametros un usuario y contraseña válidos en el sistema de activos fijos (información más detallada en el apartado del endpoint para *api/auth*).
+ Ejemplo de uso del token: http://grupodicas.com.mx/activosfijos/api/activos?token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.S_-RkIsijf7jiruMbfaH4NYbcTEy6XuHQokKdedD-gA

Se dará por implícito que todas las peticiones necesitan del parametro del token para poder usarse (a excepcion del endpoint de autenticación *api/auth*). Si se usa un token correcto, inválido o no se usa ninguno, la api responderá con un mensaje en *json* indicando el mensaje de error correspondiente.

### Uso de la paginación

De ahora en adelante, se le llamará *listado* a los registros regresados por la api que contienen registros de la base de datos. Todos los listados están en un formato de páginación con 25 elementos en cada una, y por defecto se muestra la primera página (esto se puede controlar con los parametros *page* y *page_size*).

### Formato de las respuestas
A toda petición, la api responderá en un formato de respuesta en *json* de la siguiente manera:
``` json
{
    "status": "error",
    "description": "Invalid credentials"
}
```
Los campos *status* y *description* son constantes en todas las respuestas, y cuando la petición regresa datos extra, se anexan a la respuesta:
``` json
{
    "status": "ok",
    "description": "Token sucessful generated",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.S_-RkIsijf7jiruMbfaH4NYbcTEy6XuHQokKdedD-gA"
}
```
y cuando son peticiones que regresan listados, estos se anexan como *list* a la respuesta de la siguiente manera:
``` json
{
    "status": "ok",
    "description": "Query sucessful",
    "list": [
        {
            "id": 49,
            "descripcion": "Portátil HP ProBook 655 G1",
            "clas": 7
        },
        {
            "id": 51,
            "descripcion": "Hp 245 G5 AMD A8-7410",
            "clas": 1
        },
        {
            "..."
        },
    ]
}
```
Link base de la api: http://grupodicas.com.mx/activosfijos/api/

# Endpoint: GET|HEAD :  api/auth
### Descripcion:
Proporciona un token de acceso a la api cuando el usuario y contraseña proporcionaos son válidos.
### Parametros
+ **user**: un usuario dado de alta en el sistema de inventarios actual. 
+ **passwd**: contraseña del usuario.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
+ **username**(*string*): Nombre de usuario de la persona que inició sesión.
+ **token**(*string*): Llave alfanumerica que identifica al usuario y le permite realizar peticiones a la API.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auth?user=jperez&passwd=123456

# Endpoint: GET|HEAD :  api/activos
### Descripcion:
Regresa un listado de los activos filtrado según los parámetros proporcionados.
### Parametros:
+ **page_size**(*numerico*): el numero de registros que se listan por página. Por defecto se listan 25.
+ **page**(*numerico*): la página actual. Por defecto es la 1.
+ **sort_by**(*string*): indica el nombre del campo que se usará para ordenar el listado de resultados.
+ **sort_order**(*(asc/desc)*):  indica si el ordenamiento será ascendente o descendiente.
+ **search**(*string*): lista unicamente los activos cuya descripcion coincida con la cadena de búsqueda.
+ **sin_ubicacion**(*booleano*): si vale 1, lista únicamente los activos que aún no tienen una ubicación establecida.
+ **empresa**(*numerico*): listar activos unicamente del id de la empresa indicada.
+ **departamento**(*numerico*): listar activos unicamente del id del departamento indicado.
+ **clasificacion**(*numerico*): listar activos unicamente del id de la clasificacion indicada.
+ **clasificacion**(*numerico*): listar activos unicamente del id de la clasificacion indicada.
+ **auditoria_actual**(*numerico*): Este parámetro se utiliza cuando se listan activos para una auditoria en particular. Segun la ID de auditoria proporcionada, se mostrará un campo llamado *conteo_actual* donde figurará el conteo de cada activo en la auditoria indicada, independientemente si la auditoria está marcada como guardada o no.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
+ **list**(*array*):
  + **idActivoFijo**(*int*): Identificador del activo fijo.
  + **descripcion**(*string*): Nombre o descripción del activo.
  + **conteo_guardado**(*int*): El último conteo hecho en una auditoria marcado como guardado del activo en cuestión. Si no existe conteo para dicho activo, este campo será *null* y no figurará en el los campos del activo.
  + **conteo_actual**(*int*): Segun la ID de auditoria proporcionada en el parámetro *auditoria_actual*, se mostrará en este campo el conteo de cada activo en la auditoria indicada, independientemente si la auditoria está marcada como guardada o no.
  + **fecha_conteo**(*datetime*): fecha en la que se realizo el conteo.
  + **id_auditoria_conteo**(*int*): ID de la auditoria en la que se hizo el conteo del activo.
  + **idClasificacion**(*int*): ID de la clasificación del activo.
  + **idDepartamento**(*int*): ID del departamento en el que se ubica actualmente el activo.
  + **idEmpresa**(*int*): ID de la empresa en el que se ubica actualmente el activo.
  + **ultimo_movimiento**(*datetime*): fecha en la que se estableció la ubicación actual del activo.


### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/activos?departamento=152&clasificacion=13&page_size=15&page=1&search=pascar

# Endpoint: GET|HEAD :  api/activos/{activo}
### Descripcion:
Regresa el activo que corresponda a la id especificada en {activo}.      
### Parametros:
Ninguno.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
+ **list**(*array*):
    + **idActivoFijo**(*int*): Identificador del activo fijo.
    + **descripcion**(*string*): Nombre o descripción del activo.
    + **idClasificacion**(*int*): ID de la clasificación del activo.
    + **referencia**(*string*)
    + **estatus**(*bool*): Si vale 1, el activo está de baja, por lo que no figurará en el listado general.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/activos/49

# Endpoint: GET|HEAD :  api/auditorias
### Descripcion:
Regresa un listado de las auditorias según las condiciones establecidas en los parámetros.
### Parametros:
+ **page_size**(*numerico*): el numero de registros que se listan por página. Por defecto se listan 25.
+ **page**(*numerico*): la página actual. Por defecto es la 1.
+ **sort_by**(*string*): indica el nombre del campo que se usará para ordenar el listado de resultados.
+ **sort_order**(*(asc/desc)*):  indica si el ordenamiento será ascendente o descendiente.
+ **search**(*string*): lista unicamente las auditorias cuya descripcion coincida con la cadena de búsqueda.
+ **user**(*numerico*): lista unicamente las auditorias creadas por el usuario con la id proporcionada.
+ **status**(*1/2/3*): filtrar por el estatus de la auditoria, donde
  * 1: en curso.
  * 2: terminada.
  * 3: guardada (solo entonces se puede considerar como auditoria base).
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
+ **list**(*array*):
  + **idAuditoria**(*int*):  Numero único que identifica a la auditoria.
  + **fechaCreacion**(*datetime*): fecha de creacion de la auditoria.
  + **descripcion**(*string*): Texto que describe a la auditoria, como un nombre o razon por la que se realiza.
  + **status**(*string*): el status actual de la auditoria, que puede ser:
    * *en curso*.
    * *terminada*.
    * *guardada* (solo entonces se puede considerar como auditoria base).
  + **username**(*int*): Nombre de usuario de quien creó la auditoria.
  + **terminada**(*boolean*): Vale *0* si aún está en progreso y *1* si ya se fue marcada como completada.
  + **fechaGuardada**(*datetime*): Su valor será *null* mientras la auditoria no haya sido marcada como *terminada* y posteriormente marcada como *guardada*. Cuando se guarde, *fechaGuardada* almacenará la fecha de guardado y entonces la auditoria está completamente finalizada y será la auditoria base de la siguiente que se realice.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auditorias?user=17&status=1&page_size=10&page=1

# Endpoint: POST     :  api/auditorias
### Descripcion:
Crea una nueva auditoria según los parámetros obligatorios soliciados.
### Descripcion:
Crea una nueva auditoria.
### Parametros:
+ **descripcion**(*string*): Texto que describa a la nueva auditoria, como un nombre o razon por la que se realiza.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
### Petición de ejemplo
http://localhost/api/auditorias?user=17&descripcion=Verificacion%20de%20activos%20de%20TI

# Endpoint: GET|HEAD :  api/auditorias/{auditoria}
### Descripcion:
Regresa únicamente la auditoria que corresponda a la id proporcionada en {auditoria}.
### Parametros:
Ninguno
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
+ **list**(*array*):
  + **idAuditoria**(*int*):  Numero único que identifica a la auditoria.
  + **idUser**(*int*): Numero único que identifica al usuario que creó la auditoria.
  + **descripcion**(*string*): Texto que describe a la auditoria, como un nombre o razon por la que se realiza.
  + **fechaCreacion**(*datetime*): fecha de creacion de la auditoria.
  + **terminada**(*boolean*): Vale *0* si aún está en progreso y *1* si ya se fue marcada como completada.
  + **fechaGuardada**(*datetime*): Su valor será *null* mientras la auditoria no haya sido marcada como *terminada* y posteriormente marcada como *guardada*. Cuando se guarde, *fechaGuardada* almacenará la fecha de guardado y entonces la auditoria está completamente finalizada y será la auditoria base de la siguiente que se realice.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auditorias/2

# Endpoint: PUT|PATCH:  api/auditorias/{auditoria}
### Descripcion:
Permite actualizar el status de la auditoria que corresponde a la id proporcionada en {auditoria}.
### Parametros:
+ **terminada**(*booleano*): Si vale *false*, se actualizará el campo del mismo nombre a *0*. Si vale *true*, *1* o más, se actualizará el campo a 1.
+ **guardada**(*booleano*): Si vale *1* o *true*, se marcará como guardada la auditoria estableciendo la fecha de guardado a la fecha de ese momento. `Precaución:` Una vez marcada como guardada una auditoria, ésta no se podrá volver a guardar, editar o añadir algun nuevo activo.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auditorias/2?terminada=1

# Endpoint: GET|HEAD:  api/auditorias/{id_auditoria}/activos
### Descripcion:
Regresa un listado de los activos contabilizados en la auditoria indicada en *{id_auditoria}*.
### Parametros:
+ **all**(*booleano*): Si vale `true` o `1`, muestra todos los activos contabilizados en auditorias indistintamente de a que auditoria pertenezca.
+ **user**(*int*): Dada la ID de usuario proporcionada, mostrará solo los activos contabilizados por el usuario proporcionado. Es más útil cuando se combina con el parametro *all*.
+ **activo**(*int*): Dada la ID del activo proporcionada, mostrará solo los activos contabilizados que coincidan con la ID proporcionada. Es útil cuando se combina con el parametro *all*.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
+ **list**(*array*):
  + **idAuditoria**(*int*): ID de la auditoria a la que pertenece el conteo.
  + **idActivoFijo**(*int*): ID del activo contabilizado.
  + **idUser**(*int*): ID del usuario que realizó dicho conteo.
  + **conteo**(*int*): Conteo del activo.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auditorias/2/activos?user=17


# Endpoint: POST:  api/auditorias/{id_auditoria}/activos/{id_activo}
### Descripcion:
Permite guardar un conteo de un activo. Si se llama de nuevo con la misma combinación de *{id_auditoria}* y *{id_activo}*, no se guardará una nueva entrada, si no que se actualizará la existente.
### Parametros:
+ **conteo**(*int*): `Obligatorio`. A través de este parámetro se indica la cantidad contabilizada del activo con la id indicada en *{id_activo}* que a su vez pertenece a la auditoria indicada en {id_auditoria}.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auditorias/2/activos/12?conteo=7

# Endpoint: GET|HEAD:  api/auditorias/{id_auditoria}/activos/{id_activo}
### Descripcion:
Regresa el activo contabilizado indicado en *{id_activo}* perteneciente a la auditoria indicada en *{id_auditoria}*.
### Parametros:
Ninguno.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
+ **list**(*array*):
  + **idAuditoria**(*int*): ID de la auditoria a la que pertenece el conteo.
  + **idActivoFijo**(*int*): ID del activo contabilizado.
  + **idUser**(*int*): ID del usuario que realizó dicho conteo.
  + **conteo**(*int*): Conteo del activo.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auditorias/2/activos/54

# Endpoint: PUT:  api/auditorias/{id_auditoria}/activos/{id_activo}
### Descripcion:
Permite actualizar el conteo de un activo.
### Parametros:
+ **conteo**(*int*): `Obligatorio`. A través de este parámetro se indica la cantidad contabilizada del activo con la id indicada en *{id_activo}* que a su vez pertenece a la auditoria indicada en {id_auditoria}.
### Respuesta
+ **status**(*string*): Su valor será *ok* si la peticion regresó una respuesta satisfactoria, de lo contrario, su valor será `error` o `warning`.
+ **description**(*string*): Descripcion del status superior. Es particularmente relevante cuando sucede un error o un warning, pues especifica la razón.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auditorias/2/activos/54?conteo=11

# Endpoint: GET:  api/empresas 
### Descripcion:
Regresa el listado de las empresas según los parametros ingresados. Por defecto, regresa todas ordenadas por su *id*.
### Parametros:
+ **page_size**(*numerico*): el numero de registros que se listan por página. Por defecto se listan 25.
+ **page**(*numerico*): la página actual. Por defecto es la 1.
+ **sort_by**(*string*): indica el nombre del campo que se usará para ordenar el listado de resultados.
+ **sort_order**(*(asc/desc)*):  indica si el ordenamiento será ascendente o descendiente.
+ **search**(*string*): lista unicamente los activos cuya descripcion coincida con la cadena de búsqueda.
### Respuesta
+ **idEmpresa**(*int*): ID de la empresa.
+ **nombre**(*string*): Nombre de la empresa.
+ **descripcion**(*string*): Descripción de la empresa.
+ **estatus**(*bool*): cuando vale `0` la empresa esta de baja.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/empresas/?search=cancun

# Endpoint: GET:  api/departamentos 
### Descripcion:
Regresa el listado de los departamentos según los parametros ingresados. Por defecto, regresa todos ordenadas por su *id*.
### Parametros:
+ **page_size**(*numerico*): el numero de registros que se listan por página. Por defecto se listan 25.
+ **page**(*numerico*): la página actual. Por defecto es la 1.
+ **sort_by**(*string*): indica el nombre del campo que se usará para ordenar el listado de resultados.
+ **sort_order**(*(asc/desc)*):  indica si el ordenamiento será ascendente o descendiente.
+ **search**(*string*): lista unicamente los activos cuya descripcion coincida con la cadena de búsqueda.
### Respuesta
+ **idDepartamento**(*int*): ID del departamento.
+ **idEmpresa**(*int*): ID de la empresa a la que pertenece el departamento.
+ **nombre**(*string*): Nombre del departamento.
+ **descripcion**(*string*): Descripción del departamento.
+ **estatus**(*bool*): cuando vale `0` el departamento esta de baja.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/departamentos?search=itpe