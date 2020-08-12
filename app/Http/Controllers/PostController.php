<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Post;
use App\Helpers\JwtAuth;
class PostController extends Controller
{
    public function __construct() {
        $this->middleware('api.auth',['except' => [
            'index',
            'show',
            'getImage',
            'getPostsByUser',
            'getPostsByCategory'
        ]]);
    }

    public function index(){
        
        $posts = Post::all()->load('category');
        
        return response()->json([
            'code'          =>200,
            'status'        => 'success',
            'posts'    => $posts,
        ]);
    }
    
    public function show($id){
        
        $post = Post::find($id)->load('category');
        
        if(is_object($post)){
            
            $data = array( 
                'code'      => 200,
                'status'    => 'success',
                'posts'     => $post,
            );
        }else{
            
            $data = array( 
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Post no encontrado'
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function store(Request $request){
        
        //Recoger datos
        $json = $request->input('json',null);
        
        $params_array = json_decode($json,true);
        
        if(!empty($params_array)){
            $params_array = array_map('trim',$params_array);
            
            //Validar datos
            $validate = \Validator::make($params_array,[
                'category_id'   => 'required',
                'title'         => 'required',
                'content'       => 'required',
                'image'         => 'required',
            ]);
            
            if($validate->fails()){
                
                $data = array(
                    'code'      => 400,
                    'status'    => 'error',
                    'errors'   => $validate->errors()
                );
            }else{
                
                //Conseguir id usuario autenticado
                $user = $this->getUserId($request);
                
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params_array['category_id'];
                $post->title = $params_array['title'];
                $post->content = $params_array['content'];
                $post->image = $params_array['image'];
                
                $post->save();
                
                $data = array(
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Post guardado.',
                    'post'      => $post
                );
            }
        }else{
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Datos incorrectos.'
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function update(Request $request,$id){
        
        //Recoger datos
        $json = $request->input('json',null);
        
        $params_array = json_decode($json,true);
        
        if(!empty($params_array)){
            $params_array = array_map('trim',$params_array);
            
            //Validar datos
            $validate = \Validator::make($params_array,[
                'category_id'   => 'required',
                'title'         => 'required',
                'content'       => 'required',
            ]);
            
            if($validate->fails()){
                
                $data = array(
                    'code'      => 400,
                    'status'    => 'error',
                    'errors'   => $validate->errors()
                );
            }else{
                //Quitar datos a actualizar
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['create_at']);
                
                $user = $this->getUserId($request);
                
                $post = Post::where('id',$id)
                ->where('user_id',$user->sub)
                ->first();
                
                if(is_object($post)){
                    
                    $post->update($params_array);
                    
                    $data = array(
                        'code'      => 200,
                        'status'    => 'success',
                        'message'   => 'Post actualizado.',
                        'post'      => $post
                    );
                }else{
                    $data = array(
                        'code'      => 404,
                        'status'    => 'error',
                        'message'   => 'No existe el post.'
                    );
                }
                
                /*$where = array(
                    'id'        => $id,
                    'user_id'   => $user->sub
                );
                
                $post = Post::updateOrCreate($where,$params_array);*/
                
                
            }
        }else{
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Datos incorrectos.'
            );
        }
        
        return response()->json($data,$data['code']);
    }

    public function destroy(Request $request,$id){

        $user = $this->getUserId($request);
        
        $post = Post::where('id',$id)
                ->where('user_id',$user->sub)
                ->first();
        
        if(is_object($post)){
            
            $post->delete();
            
            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Post borrado.',
                'post'      => $post
            );
            
        }else{
            $data = array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'No existe el post.'
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    private function getUserId(Request $request){
        //Comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new JwtAuth();
        //Sacar usuario identificado
        $user = $jwtAuth->checkToken($token,true);
        
        return $user;
    }
    
    public function upload(Request $request){
        //Recoger la imagen de la peticion
        $image = $request->file('file0');
        
        //Validar la imagen
        $validate = \Validator::make($request->all(),[
           'file0'  => 'required|image|mimes:jpg,jpeg,png.gif' 
        ]);
        
        //Guardar la imagen en un Disco
        if($validate->fails()){
            $data = array(
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'Error al cargar imagen.',
                'errors'    => $validate->errors()
            );
        }else{
            
            $imageName = time().$image->getClientOriginalName();
            \Storage::disk('images')->put($imageName,\File::get($image));
            
            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Imagen cargada.',
                'image'     => $imageName
            );
        }
        
        //Devolver datos
        return response()->json($data,$data['code']);
    }
    
    public function getImage($filename){

        $isset = \Storage::disk('images')->exists($filename);
        
        if($isset){
            $image = \Storage::disk('images')->get($filename);
            return new response($image,200);
        }else{
            $data = array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Archivo no encontrado.',
            );
            //Devolver datos
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function getPostsByCategory($categoryId){
        
        $posts = Post::where('category_id',$categoryId)->get();
        
        return response()->json([
                'status'    => 'success',
                'posts'     => $posts
        ],200);
    }
    
    public function getPostsByUser($UserId){
        
        $posts = Post::where('user_id',$UserId)->get();
        
        return response()->json([
                'status'    => 'success',
                'posts'     => $posts
        ],200);
    }
}
