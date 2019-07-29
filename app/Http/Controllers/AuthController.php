<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Datetime, DatetimeZone, DateInterval;

class AuthController extends Controller
{
    private static $key = 'EW-LTW2%YSzQ#Knf+P*FnYnh&9rKt77X9';
    #private static $token_expire_delay = 'P10D'; //tiempo que durará valido el token a partir de su creacion
    private static $token_expire_delay = 'PT1H';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->getToken($request->input('user'), $passwdRequested = $request->input('passwd'));        
    }

    public static function validateCredentials(Request $request)
    {
        try {
            $token = $request->input('token');
            $permisionCheck = self::checkToken($token);
            if($permisionCheck['status'] !== 'ok' ) {
                exit(response()->json($permisionCheck)->content()); //mostrar error
            } 
        } catch (\Exception $th) {
             exit(response()->json(self::status('error', $th->getMessage()))->content());
        }
    }

    private static function checkToken($jwtToken)
    {
        if(empty($jwtToken)) {
            return self::status('error', 'Invalid token supplied');
        }

        try {
            $decode = JWT::decode($jwtToken, self::$key, array('HS256'));
        } catch (\Exception $e){
            return self::status('error', $e->getMessage());
        }

        if($decode->cid !== self::clientID()) {
            return self::status('error', 'Invalid device for this token. Try sing in again');
        }

        return self::status('ok', 'Valid token, or u are a good hacker ;)');
    }

    private function getToken($userRequested, $passwdRequested)
    {
        $usuario_correcto = false;
        $userID = '';

        $users = User::all();
        foreach($users as $user) {
            if($user->username === $userRequested && $user->password === md5($passwdRequested)) {
                $usuario_correcto = true;
                $userID = $user->id;
                break;
            }         
        }

        if($usuario_correcto) {
            $expire = new DateTime('now', new DateTimeZone('America/Mexico_City'));
            $expire->add(new DateInterval(self::$token_expire_delay));

            $token = array(
                    'exp' => $expire->getTimestamp(),
                    'cid' => self::clientID(),
                    'data' => [ //credenciales
                        'username' => $userRequested,
                        'id' => $userID
                    ]
                );

            $response = self::status('ok', 'Token sucessful generated');
            $response +=['token' => JWT::encode($token, self::$key)];

            return $response;
            
        } else {
            return self::status('error', 'Invalid credentials');
        }
    }

    private static function clientID()
    {
        $clientID = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $clientID = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientID = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $clientID = $_SERVER['REMOTE_ADDR'];
        }

        $clientID .= @$_SERVER['HTTP_USER_AGENT'];
        $clientID .= gethostname();

        return sha1($clientID);
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
        
        

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
