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
```
{
    "status": "error",
    "description": "Invalid credentials"
}
```
Los campos *status* y *description* son constantes en todas las respuestas, y cuando la petición regresa datos extra, se anexan a la respuesta:
```
{
    "status": "ok",
    "description": "Token sucessful generated",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.S_-RkIsijf7jiruMbfaH4NYbcTEy6XuHQokKdedD-gA"
}
```
y cuando son peticiones que regresan listados, estos se anexan como *list* a la respuesta de la siguiente manera:
```
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
        ...
    ]
}
```
Link base de la api: http://grupodicas.com.mx/activosfijos/api/

# Endpoint
### Parametros
+ **user**: un usuario dado de alta en el sistema de inventarios actual. 
+ **passwd**: contraseña del usuario.
### Respuesta
token: 'adsd'

### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auth?user=jperez&passwd=123456

# Endpoint
GET|HEAD :  api/activos
### Parametros:
+ **page_size**(*numerico*): el numero de registros que se listan por página. Por defecto se listan 25.
+ **page**(*numerico*): la página actual. Por defecto es la 1.
+ **sort_by**(*string*): indica el nombre del campo que se usará para ordenar el listado de resultados.
+ **sort_order**(*(asc/desc)*):  indica si el ordenamiento será ascendente o descendiente.
+ **search**(*string*): lista unicamente los activos cuya descripcion coincida con la cadena de búsqueda.
+ **sinUbicacion**(*booleano*): si vale 1, lista únicamente los activos que aún no tienen una ubicación establecida.
+ **empresa**(*numerico*): listar activos unicamente del id de la empresa indicada.
+ **departamento**(*numerico*): listar activos unicamente del id del departamento indicado.
+ **clasificacion**(*numerico*): listar activos unicamente del id de la clasificacion indicada.
### Respuesta
list:
 + id
 + description
 + ...


### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/activos?departamento=152&clasificacion=13&page_size=15&page=1&search=pascar

# Endpoint
GET|HEAD :  api/activos/{activo}      
### Parametros:
Ninguno.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/activos/49

# Endpoint
GET|HEAD :  api/auditorias         
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
### Petición de ejemplo

# Endpoint
POST     :  api/auditorias
### Descripcion:
Crea una nueva auditoria.
### Parametros:
+ **user**(*numerico*): id del usuario que crea la auditoria.
+ **descripcion**(*string*): Texto que describa a la nueva auditoria, como un nombre o razon por la que se realiza.
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/auditorias?user=17&descripcion=Verificacion de activos de TI

--continuar

# Endpoint
GET|HEAD :  api/auditorias/{auditoria}
### Parametros:
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/

# Endpoint
PUT|PATCH:  api/auditorias/{auditoria}
### Parametros:
### Petición de ejemplo
http://grupodicas.com.mx/activosfijos/api/


               