<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\UserLocations;
use App\Orders;
use Carbon\Carbon;
use App\ProductsLikes;
use App\BusinessLikes;
use App\DeliveryLikes;



class UserController extends Controller
{
    public function getUser(Request $request){

      //Obtengo el token
       $token = $request->header('Authorization');
        
       //Compruebo el token
       $jwtAuth = new \JwtAuth();
       $checkToken = $jwtAuth->checkToken($token);

       $json = $request->input('json',null);
       $params_array = json_decode($json,true);

       if($checkToken && !empty($params_array)){

        $validate = \Validator::make($params_array,[
            'id'=>'required',
          ]);
    
          if($validate->fails()){
    
            $data = array('status'=>'error',
            'code'=>404,
            'message'=>'Error en el formato de los datos.',
            'errors'=>$validate->errors()
          );
       
          }else{

            $user = User::where("id",$params_array["id"])->get();

            $data = array(
                    "code"=>"200",
                    "status"=>"success",
                    "user"=>$user
                );

          }

    }else{
        $data = array(
            "code"=>"400",
            "status"=>"error",
            "message"=>"Los datos enviados no estan completos."
        );
    }
        
        echo response()->json($data,$data['code']);

    }



    public function register(Request $request){  

      //Recoger los datos del usuario por Post
      $json = $request->input('json',null);
      $params = json_decode($json); //objeto
      $params_array = json_decode($json,true); //array

       if(!empty($params) && !empty($params_array)){

      //limpiar datos
     $params_array = array_map('trim',$params_array);

      //Validar datos
      $validate = \Validator::make($params_array,[
        'name'=>'required|regex:/^[\pL\s\-]+$/u',
        'lastname'=>'required|regex:/^[\pL\s\-]+$/u',
        'email'=>'required|email|unique:users',
        'password'=>'required',
        'dob'=>'required',
        'city'=>'required',
        'phone'=>'required',
      ]);

      if($validate->fails()){
        //la validacion ha fallado
        $data = array('status'=>'error',
                      'code'=>404,
                      'message'=>'El usuario no se ha creado',
                      'errors'=>$validate->errors()
                    );
      }else{

      //Cifrar la contrasena
      $pwd = hash('sha256',$params->password);

      //Crear usuario
      $user = new User();
      $user->name = $params_array["name"];
      $user->lastname = $params_array["lastname"];
      $user->email = $params_array["email"];
      $user->password = $pwd;
      $user->dob = $params_array["dob"];
      $user->city = $params_array["city"];
      $user->phone = $params_array["phone"];


      //guardar el usuario
      $user->save();

      //enviar el token
      $jwtAuth = new \JwtAuth();
      $token = $jwtAuth->userLogIn($params->email,$pwd);

      $data = array('status'=>'success',
                    'code'=>200,
                    'message'=>'El usuario se ha creado correctamente',
                    'token'=>$token
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

   
  public function login(Request $request){
      
    $jwtAuth = new \JwtAuth();
      //Recibir datos por Post
      $json = $request->input('json',null);
      $params = json_decode($json); //objeto
      $params_array = json_decode($json,true); //array

      //$validar los datos
      $validate = \Validator::make($params_array,[
        'email'=>'required|email',
        'password'=>'required',
      ]);

      if($validate->fails()){
        //la validacion ha fallado
        $signup = array('status'=>'error',
                      'code'=>404,
                      'message'=>'El usuario no se ha podido identificar',
                      'errors'=>$validate->errors()
                    );
      }else{

        //Cifrar contrasena
        $pwd = hash('sha256',$params->password);

        //devolver token o datos
        $signup =  $jwtAuth->userLogIn($params->email,$pwd);

        if(!empty($params->getToken)){
          $signup =  $jwtAuth->userLogIn($params->email,$pwd,true);
        }

      }
      return response()->json($signup,200);

 }

   public function update(Request $request){
      
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
      
      $user = $jwtAuth->checkToken($token,true);

         
      //quitar los campos que no deseo actualizar
      unset($params_array["id"]);
      unset($params_array["owner_name"]);
      unset($params_array["password"]);
      unset($params_array["dob"]);
      unset($params_array["city"]);
      unset($params_array["category_id"]);
      unset($params_array["id_image"]);
      unset($params_array["face_image"]);
      unset($params_array["moto_image"]);
      unset($params_array["created_at"]);
     
      //actulizar usuario en DB
      $user_update = User::where('id',$user->sub)->update($params_array);

      //devolver array con resultado
      $data = array('code'=>'200',
                    'status'=>'success',
                    'message'=>$user,
                    'changes'=>$params_array);
      }else{
      $data = array('code'=>'400',
      'status'=>'error',
      'message'=>'El usuario no se ha identificado.');
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
        
    $user = $jwtAuth->checkToken($token,true);
    $validate = \Validator::make($params_array,[
      'image'=>'required',
    ]);

    if($validate->fails()){

      $data = array('status'=>'error',
      'code'=>404,
      'message'=>'la imagen se ha guardado',
      'errors'=>$validate->errors()
    );

  }else{
    $image_name = "image-".$user->email."-".time().".jpg";
    $data = base64_decode($params_array["image"]);
    \Storage::disk('user-images')->put($image_name, $data);

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
        'message'=>'El usuario no se ha identificado.');
      }

      return response()->json($data,$data["code"]);
}


   public function getImage($filename){
     $isset = \Storage::disk('user-images')->exists($filename);

     if($isset){
       $file = \Storage::disk('user-images')->get($filename);
       return Response($file,200);
     }else{
      $data = array('code'=>'400',
      'status'=>'error',
      'message'=>'La imagen no existe.');

      return response()->json($data,$data["code"]);
     }
   }

   public function info(Request $request){
    
    $token = $request->header('Authorization');
    //Compruebo el token
     $jwtAuth = new \JwtAuth();
     $checkToken = $jwtAuth->checkToken($token);

     if($checkToken){
      $usr = $jwtAuth->checkToken($token,true);

        //Buscar usuario en la base de datos
        $user = User::find($usr->sub);

        //buscar datos referentes al usuario
        $user_orders = Orders::where('user_id',$usr->sub)->get()->groupBy(function($date){
          return Carbon::parse($date->created_at)->format('Y-W');
        });

        $user_orders =  $user_orders->reverse();

        $data = array(
          'code'=>200,
          'status' => 'success',
          'user' => $user,
          'user_orders' => $user_orders,
        );
        
     }else{
      $data = array('code'=>'400',
      'status'=>'error',
      'message'=>'El usuario no se ha identificado.');
     }

     return response()->json($data,$data['code']);
   }

    public function newLocation(Request $request){

            //Obtengo el token
            $token = $request->header('Authorization');
      
            //Compruebo el token
            $jwtAuth = new \JwtAuth();
            $checkToken = $jwtAuth->checkToken($token);
      
            $json = $request->input('json',null);
            $params_array = json_decode($json,true);
      
         if($checkToken && !empty($params_array)){
              
          $user = $jwtAuth->checkToken($token,true);
          $validate = \Validator::make($params_array,[
            'city'=>'required',
            'latitude'=>'required',
            'longitude'=>'required',
            'description'=>'required',

          ]);
      
          if($validate->fails()){
      
            $data = array('status'=>'error',
            'code'=>404,
            'message'=>'El usuario no se ha gardado',
            'errors'=>$validate->errors()
          );
      
        }else{

          $location = new UserLocations();
          $location->user_id = $user->sub;
          $location->city = $params_array['city'];
          $location->latitude = $params_array['latitude'];
          $location->longitude = $params_array['longitude'];
          $location->description = $params_array['description'];

          $location->save();

          $data = array(
            'code'=>200,
            'status'=>'success',
            'message'=>'La ubicación se ha guardado correctamente.'
          );

        }
      }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El usuario no se ha identificado.');
      }

      return response()->json($data,$data["code"]);
    }

    public function updateLocation(Request $request){

      //Obtengo el token
      $token = $request->header('Authorization');

      //Compruebo el token
      $jwtAuth = new \JwtAuth();
      $checkToken = $jwtAuth->checkToken($token);

      $json = $request->input('json',null);
      $params_array = json_decode($json,true);

   if($checkToken && !empty($params_array)){
        
    $user = $jwtAuth->checkToken($token,true);
    $validate = \Validator::make($params_array,[
      'id'=>'required',
      'city'=>'required',
      'latitude'=>'required',
      'longitude'=>'required',
      'description'=>'required',

    ]);

    if($validate->fails()){

      $data = array('status'=>'error',
      'code'=>404,
      'message'=>'El usuario no se actualizado',
      'errors'=>$validate->errors()
    );

  }else{
    
    $where = array(
      'id'=>$params_array['id'],
      'user_id'=> $user->sub
    );

    unset($params_array['id']);
    $location = UserLocations::where($where)->update($params_array);

    $data = array(
      'code'=>200,
      'status'=>'success',
      'message'=>'La ubicación se ha actualizado correctamente.'
    );

  }
}else{
  $data = array('code'=>'400',
  'status'=>'error',
  'message'=>'El usuario no se ha identificado.');
}

return response()->json($data,$data["code"]);

}
public function getLocations(Request $request){
    
  $token = $request->header('Authorization');
  //Compruebo el token
   $jwtAuth = new \JwtAuth();
   $checkToken = $jwtAuth->checkToken($token);

   if($checkToken){
    $usr = $jwtAuth->checkToken($token,true);
    
    $locations = UserLocations::where('user_id',$usr->sub)->get();

      $data = array(
        'code'=>200,
        'status' => 'success',
        'locations' => $locations,
      );
      
   }else{
    $data = array('code'=>'400',
    'status'=>'error',
    'message'=>'El usuario no se ha identificado.');
   }

   return response()->json($data,$data['code']);
 }

    public function updateOrder(Request $request){

      //Obtengo el token
      $token = $request->header('Authorization');
 
      //Compruebo el token
      $jwtAuth = new \JwtAuth();
      $checkToken = $jwtAuth->checkToken($token);
      
      //Recoger los datos por POST
      $json = $request->input('json',null);
      $params_array = json_decode($json,true);
 
 
      if($checkToken && !empty($params_array)){
      
      $user = $jwtAuth->checkToken($token,true);     
 
        //Validar datos
        $validate = \Validator::make($params_array,[
         'order_id'=>'required'
       ]);
      
       if($validate->fails()){
         $data = array('status'=>'error',
         'code'=>404,
         'message'=>'Error en el formato de los datos.',
         'errors'=>$validate->errors()
       );
       }else{
             $where = array(
              'id'=>$params_array['order_id'],
              'user_id'=>$user->sub,
             );
             $update = array('shipping_status'=>5);
             $update_order = Orders::where($where)->update($update);

             $data = array('code'=>'200',
             'status'=>'success',
             'message'=>'Entrega confirmada');

       }
 
      }else{
         $data = array('code'=>'400',
         'status'=>'error',
         'message'=>'El usuario no se ha identificado.');
         }
         return response()->json($data,$data['code']);
 
     }

     public function likeProduct(Request $request){

            //Obtengo el token
            $token = $request->header('Authorization');
 
            //Compruebo el token
            $jwtAuth = new \JwtAuth();
            $checkToken = $jwtAuth->checkToken($token);
            
            //Recoger los datos por POST
            $json = $request->input('json',null);
            $params_array = json_decode($json,true);
       
            if($checkToken && !empty($params_array)){
       
              $user = $jwtAuth->checkToken($token,true);     
        
              //Validar datos
              $validate = \Validator::make($params_array,[
              'product_id'=>'required'
            ]);
            
            if($validate->fails()){
              $data = array('status'=>'error',
              'code'=>404,
              'message'=>'Error en el formato de los datos.',
              'errors'=>$validate->errors()
            );
            }else{

              //Comprobar si el usuario ya le dio like al producto
              $where = array(
                'product_id'=>$params_array['product_id'],
                'user_id'=>$user->sub,

              );
              $productLiked = ProductsLikes::where($where)->get(); 
                 
              if(!empty($productLiked[0])){

                $data = array('status'=>'error',
                'code'=>404,
                'message'=>'Ya le has dado like a este producto.',
              );

              }else{

                $productLike = new ProductsLikes();
                $productLike->product_id = $params_array["product_id"];
                $productLike->user_id = $user->sub;
                $productLike->save();

                $data = array('code'=>'200',
                'status'=>'success',
                'message'=>'Like a producto exitoso.');
              }
            }

            }else{
              $data = array('code'=>'400',
              'status'=>'error',
              'message'=>'El usuario no se ha identificado.');
              }
              return response()->json($data,$data['code']);
     }

     public function unlikeProduct(Request $request){

      //Obtengo el token
      $token = $request->header('Authorization');

      //Compruebo el token
      $jwtAuth = new \JwtAuth();
      $checkToken = $jwtAuth->checkToken($token);
      
      //Recoger los datos por POST
      $json = $request->input('json',null);
      $params_array = json_decode($json,true);
 
      if($checkToken && !empty($params_array)){
 
        $user = $jwtAuth->checkToken($token,true);     
  
        //Validar datos
        $validate = \Validator::make($params_array,[
        'product_id'=>'required'
      ]);
      
      if($validate->fails()){
        $data = array('status'=>'error',
        'code'=>404,
        'message'=>'Error en el formato de los datos.',
        'errors'=>$validate->errors()
      );
      }else{

        //Comprobar si el usuario ya le dio like al producto
        $where = array(
          'product_id'=>$params_array['product_id'],
          'user_id'=>$user->sub,

        );
        $productLiked = ProductsLikes::where($where)->get(); 
           
        if(empty($productLiked[0])){

          $data = array('status'=>'error',
          'code'=>404,
          'message'=>'Ya has cancelado tu like.',
        );

        }else{

          $productLiked = ProductsLikes::where($where)->delete(); 
          $data = array('code'=>'200',
          'status'=>'success',
          'message'=>'unlike a producto exitoso.');
        }
      }

      }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El usuario no se ha identificado.');
        }
        return response()->json($data,$data['code']);
}
    //like business

    public function likeBusiness(Request $request){

      //Obtengo el token
      $token = $request->header('Authorization');

      //Compruebo el token
      $jwtAuth = new \JwtAuth();
      $checkToken = $jwtAuth->checkToken($token);
      
      //Recoger los datos por POST
      $json = $request->input('json',null);
      $params_array = json_decode($json,true);
 
      if($checkToken && !empty($params_array)){
 
        $user = $jwtAuth->checkToken($token,true);     
  
        //Validar datos
        $validate = \Validator::make($params_array,[
        'business_id'=>'required'
      ]);
      
      if($validate->fails()){
        $data = array('status'=>'error',
        'code'=>404,
        'message'=>'Error en el formato de los datos.',
        'errors'=>$validate->errors()
      );
      }else{

        //Comprobar si el usuario ya le dio like al negocio
        $where = array(
          'business_id'=>$params_array['business_id'],
          'user_id'=>$user->sub,

        );
        $businessLiked = BusinessLikes::where($where)->get(); 
           
        if(!empty($businessLiked[0])){

          $data = array('status'=>'error',
          'code'=>404,
          'message'=>'Ya le has dado like a este producto.',
        );

        }else{

          $businessLike = new BusinessLikes();
          $businessLike->business_id = $params_array["business_id"];
          $businessLike->user_id = $user->sub;
          $businessLike->save();

          $data = array('code'=>'200',
          'status'=>'success',
          'message'=>'Like a negocio exitoso.');
        }
      }

      }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El usuario no se ha identificado.');
        }
        return response()->json($data,$data['code']);
}

public function unlikeBusiness(Request $request){

//Obtengo el token
$token = $request->header('Authorization');

//Compruebo el token
$jwtAuth = new \JwtAuth();
$checkToken = $jwtAuth->checkToken($token);

//Recoger los datos por POST
$json = $request->input('json',null);
$params_array = json_decode($json,true);

if($checkToken && !empty($params_array)){

  $user = $jwtAuth->checkToken($token,true);     

  //Validar datos
  $validate = \Validator::make($params_array,[
  'business_id'=>'required'
]);

if($validate->fails()){
  $data = array('status'=>'error',
  'code'=>404,
  'message'=>'Error en el formato de los datos.',
  'errors'=>$validate->errors()
);
}else{

  //Comprobar si el usuario ya le dio like al producto
  $where = array(
    'business_id'=>$params_array['business_id'],
    'user_id'=>$user->sub,

  );
  $productLiked = BusinessLikes::where($where)->get(); 
     
  if(empty($productLiked[0])){

    $data = array('status'=>'error',
    'code'=>404,
    'message'=>'Ya has cancelado tu like.',
  );

  }else{

    $productLiked = BusinessLikes::where($where)->delete(); 
    $data = array('code'=>'200',
    'status'=>'success',
    'message'=>'unlike a negocio exitoso.');
  }
}

}else{
  $data = array('code'=>'400',
  'status'=>'error',
  'message'=>'El usuario no se ha identificado.');
  }
  return response()->json($data,$data['code']);
}

        public function likeDelivery(Request $request){

          //Obtengo el token
          $token = $request->header('Authorization');

          //Compruebo el token
          $jwtAuth = new \JwtAuth();
          $checkToken = $jwtAuth->checkToken($token);
          
          //Recoger los datos por POST
          $json = $request->input('json',null);
          $params_array = json_decode($json,true);

          if($checkToken && !empty($params_array)){

            $user = $jwtAuth->checkToken($token,true);     

            //Validar datos
            $validate = \Validator::make($params_array,[
            'delivery_id'=>'required'
          ]);
          
          if($validate->fails()){
            $data = array('status'=>'error',
            'code'=>404,
            'message'=>'Error en el formato de los datos.',
            'errors'=>$validate->errors()
          );
          }else{

            //Comprobar si el usuario ya le dio like al negocio
            $where = array(
              'delivery_id'=>$params_array['delivery_id'],
              'user_id'=>$user->sub,

            );
            $businessLiked = DeliveryLikes::where($where)->get(); 
              
            if(!empty($businessLiked[0])){

              $data = array('status'=>'error',
              'code'=>404,
              'message'=>'Ya le has dado like a este delivery.',
            );

            }else{

              $deliveryLike = new DeliveryLikes();
              $deliveryLike->delivery_id = $params_array["delivery_id"];
              $deliveryLike->user_id = $user->sub;
              $deliveryLike->save();

              $data = array('code'=>'200',
              'status'=>'success',
              'message'=>'Like a delivery exitoso.');
            }
          }

          }else{
            $data = array('code'=>'400',
            'status'=>'error',
            'message'=>'El usuario no se ha identificado.');
            }
            return response()->json($data,$data['code']);
        }

        public function unlikeDelivery(Request $request){

        //Obtengo el token
        $token = $request->header('Authorization');

        //Compruebo el token
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        //Recoger los datos por POST
        $json = $request->input('json',null);
        $params_array = json_decode($json,true);

        if($checkToken && !empty($params_array)){

        $user = $jwtAuth->checkToken($token,true);     

        //Validar datos
        $validate = \Validator::make($params_array,[
        'delivery_id'=>'required'
        ]);

        if($validate->fails()){
        $data = array('status'=>'error',
        'code'=>404,
        'message'=>'Error en el formato de los datos.',
        'errors'=>$validate->errors()
        );
        }else{

        //Comprobar si el usuario ya le dio like al producto
        $where = array(
        'delivery_id'=>$params_array['delivery_id'],
        'user_id'=>$user->sub,

        );
        $productLiked = DeliveryLikes::where($where)->get(); 
        
        if(empty($productLiked[0])){

        $data = array('status'=>'error',
        'code'=>404,
        'message'=>'Ya has cancelado tu like.',
        );

        }else{

        $productLiked = DeliveryLikes::where($where)->delete(); 
        $data = array('code'=>'200',
        'status'=>'success',
        'message'=>'unlike a negocio exitoso.');
        }
        }

        }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El usuario no se ha identificado.');
        }
        return response()->json($data,$data['code']);
        }

        //feed by city
        //search
        //get business 
        //make a order


    

    
}
