<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Cargando clases
use \App\Http\Middleware\ApiAuthMiddleware;

Route::get('/', function () {
    return "<h1>Hola mundo</h1>";
});

Route::get('welcome', function () {
    return view('welcome');
});



/*******************************************************************************
*                                Rutas del API 
*******************************************************************************/
    /*Metodos HTTP Comunes
     * GET: Conseguir datos o recursos.
     * POST: Guardar datos o recursos o hacer una logica y devolver algo.
     * PUT: Para Actualizar datos o recursos.
     * DELETE: Eliminar datos o recuros.
     */

    //Rutas del controlador de User
        Route::post('/api/register','UserController@register');
        Route::post('/api/login','UserController@login');
        Route::put('/api/user/update','UserController@update');
        Route::post('/api/user/upload','UserController@upload')->middleware(ApiAuthMiddleware::class);
        Route::get('/api/user/downloadAvatar/{filename}','UserController@getImage');
        Route::get('/api/user/detail/{id}','UserController@detail');
    
    //Rutas del controlador Category
        Route::resource('/api/category','CategoryController');
        
    //Rutas del controlador Post
        Route::resource('/api/post','PostController');
        Route::post('/api/post/upload','PostController@upload');
        Route::get('/api/post/downloadImage/{filename}','PostController@getImage');
        Route::get('/api/post/category/{categoryId}','PostController@getPostsByCategory');
        Route::get('/api/post/user/{userId}','PostController@getPostsByUser');
        
/*******************************************************************************
*                                Rutas de Prueba 
*******************************************************************************/
/*Route::get('/animales', 'PruebasController@index');
Route::get('/test-orm', 'PruebasController@testOrm');

Route::get('rutapruebas/{nombre?}', function($nombre = null){
    $texto = '<h1>Texto desde una ruta de prueba</h1> ';
    $texto.= 'Nombre: '.$nombre;
   
    return view('pruebas', array(
        'texto' => $texto
    ));
});*/