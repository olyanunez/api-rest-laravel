<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Category;
class CategoryController extends Controller
{
    public function __construct() {
        $this->middleware('api.auth',['except' => ['index','show']]);
    }
    
    public function index(){
        $categories = Category::all();
        
        return response()->json([
            'code'          =>200,
            'status'        => 'success',
            'categories'    => $categories,
        ]);
    }
    
    public function show($id){
        $category = Category::find($id);
        
        if(is_object($category)){
            $data = array(
                'code'      =>200,
                'status'    => 'success',
                'category'  => $category,
            );
        }else{
            $data = array(
                'code'      => 404,
                'status'    => 'success',
                'message'   => 'Categoria no encontrada.'   
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function store(Request $request){
        
        //Recoger los datos
        $json = $request->input('json',null);
        $params_array = json_decode($json,true);
        
        if(!empty($params_array)){
            //Limpiar datos
            $params_array = array_map('trim', $params_array);
            
            //Validar los datos
            $validate = \Validator::make($params_array,[
                'name'  => 'required|alpha|unique:categories'
            ]);

            if($validate->fails()){
                $data = array(
                    'code'      => 400,
                    'status'    => 'error',
                    'errors'    =>  $validate->errors()
                );
            }else{
                //Limpiar datos
                $params_array = array_map('trim',$params_array);

                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();

                $data = array(
                    'code'      =>200,
                    'status'    => 'success',
                    'category'  => $category,
                );
            }
        }else{
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'messege'   => 'No has enviado ningun dato.',
            );
        }
        return response()->json($data,$data['code']);
    }
    
    public function update(Request $request,$id){
        
        //Recoger los datos
        $json = $request->input('json',null);
        
        $params_array = json_decode($json,true);
        
        if(!empty($params_array)){
            //Validar los datos
            $validate = \Validator::make($params_array,[
                'name'  => 'required|unique:categories'
            ]);
            
            unset($params_array['id']);
            unset($params_array['create_at']);
                
            if($validate->fails()){
                $data = array(
                    'code'      => 400,
                    'status'    => 'error',
                    'errors'    =>  $validate->errors()
                );
            }else{
                //Limpiar datos
                $params_array = array_map('trim',$params_array);
                
                Category::where('id',$id)->update($params_array);
                $category = Category::find($id);
                
                $data = array(
                    'code'      =>200,
                    'status'    => 'success',
                    'category'  => $category,
                );
            }
        }else{
            $data = array(
                'status'    => 'error',
                'code'      => 400,
                'messege'   => 'No has enviado ningun dato.',
            );
        }
        return response()->json($data,$data['code']);
    }
}
