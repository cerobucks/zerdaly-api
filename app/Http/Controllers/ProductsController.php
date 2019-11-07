<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Business;
use App\Products;
use App\ProductsLikes;

class ProductsController extends Controller
{
    public function newProduct(Request $request){

        $token = $request->header('Authorization');
        //Compruebo el token
         $jwtAuth = new \JwtAuth();
         $checkToken = $jwtAuth->checkToken($token);
  
        //Obtener datos por el metodo POST
         $json = $request->input('json',null);
         $params = json_decode($json);
         $params_array = json_decode($json,true);
  
         if($checkToken && !empty($params) && !empty($params_array)){
          
          //Validar datos
          $validate = \Validator::make($params_array,[
            'name'=>'required',
            'price'=>'required',
            'on_stock'=>'required',
            'image'=>'required',
            'active' => 'required',
             ]);
       
             if($validate->fails()){
                $data = array('status'=>'error',
                'code'=>404,
                'message'=>'El producto no se ha creado',
                'errors'=>$validate->errors()
              );
             }else{
               $bs = $jwtAuth->checkToken($token,true);
               $business = Business::find($bs->sub);
               $product = new Products();

               $product->business_id = $business->id;
               $product->category_id = $business->category_id;
               $product->name = $params_array['name'];
               $product->price = $params_array['price'];
               $product->description = $params_array['description'];
               $product->on_stock = $params_array['on_stock'];
               $product->image = $params_array['image'];
               $product->active = $params_array['active'];
               $product->save();

               $data = array(
                 'status'=>'success',
               'code'=>200,
               'message'=>'El producto se ha creado correctamente',
             );
             }
         }else{
            $data = array('status'=>'error',
            'code'=>404,
            'message'=>'Los datos enviados no son correctos',
          );
         }

         return response()->json($data,$data["code"]);
       }

      public function updateProduct(Request $request){
        
                //Obtengo el token
                $token = $request->header('Authorization');
        
                //Compruebo el token
                $jwtAuth = new \JwtAuth();
                $checkToken = $jwtAuth->checkToken($token);
                
                //Recoger los datos por POST
                $json = $request->input('json',null);
                $params = json_decode($json);
                $params_array = json_decode($json,true);
        
                if($checkToken && !empty($params_array)){
                
                $business = $jwtAuth->checkToken($token,true);
        
                //Validar datos
                $validate = \Validator::make($params_array,[
                  'id'=>'required',
                  'name'=>'required',
                  'price'=>'required',
                  'on_stock'=>'required',
                  'image' => 'required',
                  'active' => 'required',
                  'description' => 'required',
                ]);

                $id = $params_array["id"];
                //quitar los campos que no deseo actualizar
                unset($params_array["id"]);
                unset($params_array["created_at"]);
               
                //actulizar negocio en DB
                $products_update = Products::where('id',$id)->update($params_array);
        
                //devolver array con resultado
                $data = array('code'=>'200',
                              'status'=>'success',
                              'message'=>$business,
                              'changes'=>$params_array);

                }else{
                $data = array('code'=>'400',
                'status'=>'error',
                'message'=>'El negocio no se ha identificado.');
                }
                return response()->json($data,$data['code']);
        
}

      public function upload(Request $request){
      
        //Obtengo el token
        $token = $request->header('Authorization');
        
        //Compruebo el token
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        $json = $request->input('json',null);
        $params_array = json_decode($json,true);

     if($checkToken && !empty($params_array)){
          
      $business = $jwtAuth->checkToken($token,true);
      $validate = \Validator::make($params_array,[
        'image'=>'required',
      ]);

      if($validate->fails()){

        $data = array('status'=>'error',
        'code'=>404,
        'message'=>'la imagen no se ha guardado',
        'errors'=>$validate->errors()
      );
   
       }else{

        $image_name = "product-".$business->email."-".time().".jpg";
        $data = base64_decode($params_array["image"]);
        \Storage::disk('business-posts')->put($image_name, $data);
  
       //devolver el resultado
        $data = array(
          'code'=>200,
          'status' => 'success',
          'image' => $image_name
          );

       }
        }else{
          $data = array('code'=>'400',
          'status'=>'error',
          'message'=>'El negocio no se ha identificado.');
        }

        return response()->json($data,$data["code"]);
}

    
      public function getImage($filename){

       $isset = \Storage::disk('business-posts')->exists($filename);

       if($isset){
         $file = \Storage::disk('business-posts')->get($filename);
         return Response($file,200);
       }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'La imagen no existe.');

        return response()->json($data,$data["code"]);
       }
}
      
   //update product   
   public function update(Request $request){
    $token = $request->header('Authorization');
    //Compruebo el token
     $jwtAuth = new \JwtAuth();
     $checkToken = $jwtAuth->checkToken($token);

    //Obtener datos por el metodo POST
     $json = $request->input('json',null);
     $params_array = json_decode($json,true);

     if($checkToken && !empty($params_array)){
      
      //Validar datos
      $validate = \Validator::make($params_array,[
        'id'=>'required',
        'name'=>'required',
        'price'=>'required',
        'on_stock'=>'required',
        'image'=>'required',
        'active' => 'required',
         ]);
   
         if($validate->fails()){
            $data = array('status'=>'error',
            'code'=>404,
            'message'=>'El producto no se ha creado',
            'errors'=>$validate->errors()
          );
         }else{

          $bs = $jwtAuth->checkToken($token,true);
           
          $where = array(
            'id'=>$params_array['id'],
            'business_id'=>$bs->sub,

          );
          unset($params_array['id']);
          unset($params_array['business_id']);
          unset($params_array['created_at']);
          unset($params_array['updated_at']);


          $product = Products::where($where)->update($params_array);

          $data = array('status'=>'success',
          'code'=>200,
          'message'=>'El producto se ha actualizado correctamente',
        );
           
         }

        }else{
          $data = array('status'=>'error',
          'code'=>404,
          'message'=>'Los datos enviados no son correctos',
        );
       }

       return response()->json($data,$data["code"]);
   }

   //Get product by ID
   public function getProductById($id){
    
    $product = Products::where('id',$id)->get();
    $product_likes = ProductsLikes::where('product_id',$id)->get();

    if(is_object($product) && !empty($product)){
      
      $data = array('status'=>'success',
      'code'=>200,
      'product'=>$product,
      'likes'=> $product_likes
    );

    }else{
      $data = array('status'=>'error',
      'code'=>404,
      'message'=>'Este producto no existe.',
    );
    }
       return response()->json($data,$data["code"]);

   }
     
}
