<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\User;

class JwtAuth{
    
    private $key;
    
    public function __construct() {
        $this->key = 'esto_es_una_clave_que_no-sabras-nunca-jajaja';
    }
    
    public function signup($email,$password, $getDataToken = null) {
        
        //Buscar si existe el usuario con sus credenciales
        $user = User::where([
            'email'     => $email,
            'password'  => $password
        ])->first();
        
        //Comprobar si son correctas
        $signup = false;
        if(is_object($user)){
            $signup = true;
        }
        
        //Generar el Token con los datos del usuario identificado
        if($signup){
            $token = array(
                'sub'       => $user->id,
                'email'     => $user->email,
                'name'      => $user->name,
                'surname'   => $user->surname,
                'iat'       => time(),
                'exp'       => time() + (7 * 24 * 60 * 60)
            );
            
            $jwt = JWT::encode($token, $this->key,'HS256');
            $decoded = JWT::decode($jwt, $this->key,['HS256']);

            //Devolver los datos decodificados o el Token, en funcion de un parametro
            
            if(is_null($getDataToken)){
                $data = $jwt;
            }else{
                $data = $decoded;
            }
            
        }else{
            $data = array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Login incorrecto.'
            );
        }
        
        return $data;
    }
    
    public function checkToken($jwt, $getIdentity = false) {
        
        $auth = false;
        $jwt = str_replace('"', '', $jwt);
        
        try{
            $decoded = JWT::decode($jwt, $this->key,['HS256']);
        } catch (\UnexpectedValueException $e){
            $auth = false;
        } catch (\DomainException $e){
            $auth = false;
        }
        
        if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }
        
        if($getIdentity){
            return $decoded;
        }
        
        return $auth;
    }
}
