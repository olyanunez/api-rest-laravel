<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
class UserController extends Controller
{
    
    public function register(Request $request) {
        
        //Recoger los datos por post
        $json = $request->input('json',null);
        
        $params = json_decode($json);//Objeto
        $params_array = json_decode($json,true);//Array
        
        if(!empty($params_array) && !empty($params)){
            
            //Limpiar datos
            $params_array = array_map('trim',$params_array);

            //Validar datos
            $validate = \Validator::make($params_array,[
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users', //Comprobar si ya existe el usuario
                'password'  => 'required'
            ]);

            if($validate->fails()){
                
                //Validacion fallida
                $data = array(
                    'status'    => 'error',
                    'code'      => 400,
                    'messege'   => 'El usuarios no se ha creado correctamente.',
                    'errors'    => $validate->errors()
                );
            }else{
                
                //Validacion pasada correctamente
                   
                // Cifrar passowrd
                $pwd = hash('sha256',$params->password);
                
                //Crear usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';
                
                //Guardar el Usuario
                $user->save();
                
                $data = array(
                    'status'    => 'success',
                    'code'      => 200,
                    'messege'   => 'El usuario se ha creado correctamente.',
                    'user'      => $user
                );
            }
        }else{
            
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'messege'   => 'Los datos enviados no son correctos.',
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function login(Request $request) {
        
        //Recoger los datos por POST
        $json = $request->input('json',null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        
        //Validar los datos
        $validate = \Validator::make($params_array,[
            'email'     => 'required|email',
            'password'  => 'required'
        ]);
        
        if($validate->fails()){
            
            //Validacion fallida
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'El usuario no se ha podido identificar.',
                'errors'    => $validate->errors()
            );
        }else{
            
            //Validacion pasada

            //Cifrar password
            $password = hash('sha256',$params->password);
            
            $jwtAuth = new \JwtAuth();
            
            if(!empty($params->gettoken)){
                $data = $jwtAuth->signup($params->email,$password,true);
            }else{
                $data = $jwtAuth->signup($params->email,$password);
            }
        }
        
        return response()->json($data);
    }
    
    public function update(Request $request){
        //Comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        
        $jwtAuth = new \JwtAuth();
        
        $checkToken = $jwtAuth->checkToken($token);
        //Recoger los datos por post
        $json = $request->input('json',null);
        $params_array = json_decode($json,true);//Array
        
        if($checkToken && !empty($params_array)){
            
            //Sacar usuario identificado
            $user = $jwtAuth->checkToken($token,true);
           
            //Validar datos
            $validate = \Validator::make($params_array,[
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users,'.$user->sub,
            ]);
            
//            if($validate->fails()){
//                $data = array(
//                    'code'      => 400,
//                    'status'    => 'error',
//                    'message'   => 'Datos incorrectos.',
//                    'errors'    => $validate->errors()
//                );
//            }else{
                //Quitar campos que no quiero actualizar
                unset($params_array['id']);
                unset($params_array['role']);
                unset($params_array['create_at']);
                unset($params_array['remember_token']);
            
                //Actualizar usuario en bbdd
                $userUpdate = User::where('id',$user->sub)->update($params_array);
                
                //Devolver array con resultado
                $data = array(
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Usuario Actualizado.',
                    'user'      => $params_array,
                );
            //}
        }else{
            
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'El usuario no se ha identificado.',
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function upload(Request $request) {
        //Recoger los datos de la peticion
        $image = $request->file('file0');
        
        //Validar imagen
        $validate = \Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,png,gif,jpeg'
        ]);
        
        //Guardar imagen
        if($image && !$validate->fails()){
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name,\File::get($image));
            
            //Devolver el resultado
            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Imagen cargada correctamente.',
                'image'     => $image_name
            );
            
        }else{
            
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al subir imagen.',
                'error'     => $validate->errors()
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function getImage($filename){
        $isset = \Storage::disk('users')->exists($filename);
        
        if($isset){
            $file = \Storage::disk('users')->get($filename);
            return new response($file,200);
        }else{
            $data = array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'No existe la imagen.',
            );
            
            return response()->json($data,$data['code']);
        }
    }
    
    public function detail($id){
        $user = User::find($id);
        
        if(is_object($user)){
            $data = array(
                'code'      => 200,
                'status'    => 'succes',
                'message'   => 'Usuario encontrado.',
                'user'      => $user
            );
        }else{
            $data = array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Usuario no encontrado.',
            );
        }
        
        return response()->json($data,$data['code']);
    }
}
