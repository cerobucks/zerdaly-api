<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Delivery;
use App\DeliveryBank;
use App\DeliveryLikes;
use App\DeliveryRequest;
use App\Orders;
use App\User;
use App\UserLocations;
use App\Business;
use App\Products;
use Carbon\Carbon;


class DeliveryController extends Controller
{
 
    public function register(Request $request){  

        //Recoger los datos del delivery por Post
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
          'email'=>'required|email|unique:delivery',
          'password'=>'required',
          'dob'=>'required',
          'city'=>'required',
          'phone'=>'required',
          'latitude'=>'required',
          'longitude'=>'required',
          'id_image'=>'required',
          'face_image'=>'required',
          'moto_image'=>'required',

        ]);
  
        if($validate->fails()){
          //la validacion ha fallado
          $data = array('status'=>'error',
                        'code'=>404,
                        'message'=>'El usuario no se ha creado',
                        'errors'=>$validate->errors()
                      );
        }else{
        //Guardar Imagenes ID, Face, Moto
        $id_image_name = "id-image-".$params_array["name"]."-".time().".jpg";
        $data = base64_decode($params_array["id_image"]);
        \Storage::disk('id-images')->put($id_image_name, $data);

        $face_image_name = "face-image-".$params_array["name"]."-".time().".jpg";
        $data = base64_decode($params_array["face_image"]);
        \Storage::disk('face-images')->put($face_image_name, $data);

        $moto_image_name = "face-image-".$params_array["name"]."-".time().".jpg";
        $data = base64_decode($params_array["moto_image"]);
        \Storage::disk('moto-images')->put($face_image_name, $data);

  
        //Cifrar la contrasena
        $pwd = hash('sha256',$params->password);
  
        //Crear usuario
        $delivery = new Delivery();
        $delivery->name = $params_array["name"];
        $delivery->lastname = $params_array["lastname"];
        $delivery->email = $params_array["email"];
        $delivery->password = $pwd;
        $delivery->dob = $params_array["dob"];
        $delivery->city = $params_array["city"];
        $delivery->phone = $params_array["phone"];
        $delivery->latitude = $params_array["latitude"];
        $delivery->longitude = $params_array["longitude"];
        $delivery->id_image = $id_image_name;
        $delivery->face_image = $face_image_name;
        $delivery->moto_image = $moto_image_name;
        $delivery->validated = 0;
        $delivery->status = 1;

        //guardar el delivery
        $delivery->save();

        //enviar el token
        $jwtAuth = new \JwtAuth();
        $token = $jwtAuth->deliveryLogIn($params->email,$pwd);

        $data = array('status'=>'success',
                      'code'=>200,
                      'message'=>'El delivery se ha creado correctamente',
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
                        'message'=>'El delivery no se ha podido identificar',
                        'errors'=>$validate->errors()
                      );
        }else{
  
          //Cifrar contrasena
          $pwd = hash('sha256',$params->password);
  
          //devolver token o datos
          $signup =  $jwtAuth->deliveryLogIn($params->email,$pwd);
  
          if(!empty($params->getToken)){
            $signup =  $jwtAuth->deliveryLogIn($params->email,$pwd,true);
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
        
        $delivery = $jwtAuth->checkToken($token,true);

           
        //quitar los campos que no deseo actualizar
        unset($params_array["id"]);
        unset($params_array["owner_name"]);
        unset($params_array["password"]);
        unset($params_array["dob"]);
        unset($params_array["city"]);
        unset($params_array["id_image"]);
        unset($params_array["face_image"]);
        unset($params_array["moto_image"]);
        unset($params_array["created_at"]);
       
        //actulizar negocio en DB
        $delivery_update = Delivery::where('id',$delivery->sub)->update($params_array);

        //devolver array con resultado
        $data = array('code'=>'200',
                      'status'=>'success',
                      'message'=>$delivery,
                      'changes'=>$params_array);
        }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El delivery no se ha identificado.');
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
          
      $delivery = $jwtAuth->checkToken($token,true);
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
      $image_name = "image-".$delivery->email."-".time().".jpg";
      $data = base64_decode($params_array["image"]);
      \Storage::disk('delivery-images')->put($image_name, $data);

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
          'message'=>'El delivery no se ha identificado.');
        }

        return response()->json($data,$data["code"]);
}

  
     public function getImage($filename){
       $isset = \Storage::disk('delivery-images')->exists($filename);

       if($isset){
         $file = \Storage::disk('delivery-images')->get($filename);
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
        $dly = $jwtAuth->checkToken($token,true);

          //Buscar delivery en la base de datos
          $delivery = Delivery::find($dly->sub);

          //buscar datos referentes al delivery
          $delivery_orders = Orders::where('delivery_id',$dly->sub)->get()->groupBy(function($date){
            return Carbon::parse($date->created_at)->format('Y-W');
          });

          $delivery_orders =  $delivery_orders->reverse();

          $delivery_contact = DeliveryRequest::where('delivery_id',$dly->sub)->get()->groupBy(function($date){
            return Carbon::parse($date->created_at)->format('Y-W');
          });

          $delivery_likes = DeliveryLikes::where('delivery_id',$dly->sub)->get();

          $data = array(
            'code'=>200,
            'status' => 'success',
            'delivery' => $delivery,
            'delivery_orders' => $delivery_orders,
            'delivery_contact' => $delivery_contact,
            'delivery_likes' => $delivery_likes,
          );
          
       }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El delivery no se ha identificado.');
       }

       return response()->json($data,$data['code']);
     }

     public function newBank(Request $request){

      $token = $request->header('Authorization');
      //Compruebo el token
       $jwtAuth = new \JwtAuth();
       $checkToken = $jwtAuth->checkToken($token);

       $json = $request->input('json',null);
       $params_array = json_decode($json,true);

       if($checkToken && !empty($params_array)){

        $validate = \Validator::make($params_array,[
          'bank_name'=>'required',
          'account_type'=>'required',
          'account_holder'=>'required',
          'account_number'=>'required',
        ]);

        if($validate->fails()){

          $data = array('status'=>'error',
          'code'=>404,
          'message'=>'La cuenta de banco no se ha guardado',
          'errors'=>$validate->errors()
        );

      }else{
        
        $dly = $jwtAuth->checkToken($token,true);
        $bank = new DeliveryBank();
      
        $bank->delivery_id = $dly->sub;
        $bank->bank_name = $params_array["bank_name"];
        $bank->account_type = $params_array["account_type"];
        $bank->account_holder = $params_array["account_holder"];
        $bank->account_number = $params_array["account_number"];

        $bank->save();
        
        //Actualizar Estado del Delivery
        $update = array('validated'=>1);
        $delivery = Delivery::where('id',$dly->sub)->update($update);

        $data = array(
          'code'=>'200',
          'status'=>'success',
          'bank' => $bank
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

     public function updateBank(Request $request){
        
      //Obtengo el token
      $token = $request->header('Authorization');

      //Compruebo el token
      $jwtAuth = new \JwtAuth();
      $checkToken = $jwtAuth->checkToken($token);
      
      //Recoger los datos por POST
      $json = $request->input('json',null);
      $params_array = json_decode($json,true);


      if($checkToken && !empty($params_array)){
      
      $delivery = $jwtAuth->checkToken($token,true);

      //Validar datos
      $validate = \Validator::make($params_array,[
        'bank_name'=>'required',
        'account_type'=>'required',
        'account_holder'=>'required',
        'account_number'=>'required',
      ]);
     
      if($validate->fails()){
        $data = array('status'=>'error',
        'code'=>404,
        'message'=>'Error en el formato de los datos.',
        'errors'=>$validate->errors()
      );
      }else{
      //actulizar banco en DB
      $bank_update = DeliveryBank::where('delivery_id',$delivery->sub)->update($params_array);

      //devolver array con resultado
      $data = array('code'=>'200',
                    'status'=>'success',
                    'message'=>$delivery,
                    'changes'=>$params_array);

      }

      }else{
      $data = array('code'=>'400',
      'status'=>'error',
      'message'=>'El delivery no se ha identificado.');
      }
      return response()->json($data,$data['code']);

}
    public function getBank(Request $request){

        //Obtengo el token
      $token = $request->header('Authorization');

      //Compruebo el token
      $jwtAuth = new \JwtAuth();
      $checkToken = $jwtAuth->checkToken($token);
      
      if($checkToken){
      
      $delivery = $jwtAuth->checkToken($token,true);

      $bank = DeliveryBank::where('delivery_id',$delivery->sub)->get();

            //devolver array con resultado
            $data = array('code'=>'200',
            'status'=>'success',
            'message'=>$bank);

       }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El delivery no se ha identificado.');
        }
        return response()->json($data,$data['code']);
  
    }

    public function updateDeliveryRequest(Request $request){

              //Obtengo el token
      $token = $request->header('Authorization');

      //Compruebo el token
      $jwtAuth = new \JwtAuth();
      $checkToken = $jwtAuth->checkToken($token);
      
      //Recoger los datos por POST
      $json = $request->input('json',null);
      $params_array = json_decode($json,true);


      if($checkToken && !empty($params_array)){
      
      $delivery = $jwtAuth->checkToken($token,true);

      //Validar datos
      $validate = \Validator::make($params_array,[
        'status'=>'required',
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
            'delivery_id'=>$delivery->sub,
            'order_id'=>$params_array['order_id'],
        );

        unset($params_array['order_id']);

        $deliveryRequest = DeliveryRequest::where($where)->update($params_array);
      
        //devolver array con resultado
         $data = array(
                    'code'=>'200',
                    'status'=>'success',
                    'message'=>$delivery,
                    'changes'=>$params_array);
        
      }

    }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El delivery no se ha identificado.');
        }
        return response()->json($data,$data['code']);
    }

    //delivery get order
    public function getOrder(Request $request){
    //Obtengo el token
      $token = $request->header('Authorization');

      //Compruebo el token
      $jwtAuth = new \JwtAuth();
      $checkToken = $jwtAuth->checkToken($token);
      
      //Recoger los datos por POST
      $json = $request->input('json',null);
      $params_array = json_decode($json,true);


      if($checkToken && !empty($params_array)){
      
      $delivery = $jwtAuth->checkToken($token,true);

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
         
        $order = Orders::where("id",$params_array["order_id"])->get();
        //devolver array con resultado
                $data = array(
                    'code'=>'200',
                    'status'=>'success',
                    'message'=>$order);

      }
    }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El delivery no se ha identificado.');
        }
        return response()->json($data,$data['code']);
    }
    //delivery takes the order
    public function takeOrder(Request $request){

     //Obtengo el token
     $token = $request->header('Authorization');

     //Compruebo el token
     $jwtAuth = new \JwtAuth();
     $checkToken = $jwtAuth->checkToken($token);
     
     //Recoger los datos por POST
     $json = $request->input('json',null);
     $params_array = json_decode($json,true);


     if($checkToken && !empty($params_array)){
     
     $delivery = $jwtAuth->checkToken($token,true);     

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
         
        $order = Orders::where("id",$params_array["order_id"])->get();

        if(empty($order[0]->delivery_id)){

            $update = array(
                'delivery_id'=>$delivery->sub,
                'shipping_status'=>1);
            $update_order = Orders::where('id',$params_array['order_id'])->update($update);

            $data = array('code'=>'200',
            'status'=>'success',
            'message'=>'Orden tomada correctamente.');


        }else{
            $data = array('code'=>'400',
            'status'=>'error',
            'message'=>'Esta orden ha sido tomada por otro delivery.');
        }
      }

     }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El delivery no se ha identificado.');
        }
        return response()->json($data,$data['code']);

    }
    //delivery delivery arrived in the business
    public function deliveryArrivedOnBusinessPlace(Request $request){

        //Obtengo el token
        $token = $request->header('Authorization');
   
        //Compruebo el token
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        
        //Recoger los datos por POST
        $json = $request->input('json',null);
        $params_array = json_decode($json,true);
   
   
        if($checkToken && !empty($params_array)){
        
        $delivery = $jwtAuth->checkToken($token,true);     
   
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
            
           $order = Orders::where("id",$params_array["order_id"])->get();
   
           if($order[0]->delivery_id == $delivery->sub){
   
               $update = array('shipping_status'=>2);
               $update_order = Orders::where('id',$params_array['order_id'])->update($update);

             //Notificar al delivery
              /*      define('API_ACCESS_KEY','AIzaSyBHUE3XD7onAUJbZwsAkgKc7mYbbyqkPec');
                    $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
                    $token = $delivery[0]->notification_token;
                        $notification = [
                               'title' =>'Zerdaly Business',
                               'body' => '¡'.$bs->business_name.' te solícita para un envío!',
                               'icon' =>'myIcon', 
                               'sound' => 'mySound'
                           ];
                           $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];
                   
                           $fcmNotification = [
                               //'registration_ids' => $tokenList, //multple token array
                               'to'        => $token, //single token
                               'notification' => $notification,
                               'data' => $extraNotificationData
                           ];
                   
                           $headers = [
                               'Authorization: key=' . API_ACCESS_KEY,
                               'Content-Type: application/json'
                           ];
                   
                   
                           $ch = curl_init();
                           curl_setopt($ch, CURLOPT_URL,$fcmUrl);
                           curl_setopt($ch, CURLOPT_POST, true);
                           curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                           curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                           curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
                           $result = curl_exec($ch);
                           curl_close($ch);

                           */
   
               $data = array('code'=>'200',
               'status'=>'success',
               'message'=>'Delivery llego al negocio exitosamente.');
   
   
           }else{
               $data = array('code'=>'400',
               'status'=>'error',
               'message'=>'Esta orden ha sido tomada por otro delivery.');
           }
         }
   
        }else{
           $data = array('code'=>'400',
           'status'=>'error',
           'message'=>'El delivery no se ha identificado.');
           }
           return response()->json($data,$data['code']);
   
       }

    //delivery is on it way to the customer
    public function deliveryOnWayToCustomerPlace(Request $request){

        //Obtengo el token
        $token = $request->header('Authorization');
   
        //Compruebo el token
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        
        //Recoger los datos por POST
        $json = $request->input('json',null);
        $params_array = json_decode($json,true);
   
   
        if($checkToken && !empty($params_array)){
        
        $delivery = $jwtAuth->checkToken($token,true);     
   
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
            
           $order = Orders::where("id",$params_array["order_id"])->get();
   
           if($order[0]->delivery_id == $delivery->sub){
   
               $update = array('shipping_status'=>3);
               $update_order = Orders::where('id',$params_array['order_id'])->update($update);

             //Notificar al delivery
              /*      define('API_ACCESS_KEY','AIzaSyBHUE3XD7onAUJbZwsAkgKc7mYbbyqkPec');
                    $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
                    $token = $delivery[0]->notification_token;
                        $notification = [
                               'title' =>'Zerdaly Business',
                               'body' => '¡'.$bs->business_name.' te solícita para un envío!',
                               'icon' =>'myIcon', 
                               'sound' => 'mySound'
                           ];
                           $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];
                   
                           $fcmNotification = [
                               //'registration_ids' => $tokenList, //multple token array
                               'to'        => $token, //single token
                               'notification' => $notification,
                               'data' => $extraNotificationData
                           ];
                   
                           $headers = [
                               'Authorization: key=' . API_ACCESS_KEY,
                               'Content-Type: application/json'
                           ];
                   
                   
                           $ch = curl_init();
                           curl_setopt($ch, CURLOPT_URL,$fcmUrl);
                           curl_setopt($ch, CURLOPT_POST, true);
                           curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                           curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                           curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
                           $result = curl_exec($ch);
                           curl_close($ch);

                           */
   
               $data = array('code'=>'200',
               'status'=>'success',
               'message'=>'Delivery de camino a donde el cliente.');
   
   
           }else{
               $data = array('code'=>'400',
               'status'=>'error',
               'message'=>'Esta orden ha sido tomada por otro delivery.');
           }
         }
   
        }else{
           $data = array('code'=>'400',
           'status'=>'error',
           'message'=>'El delivery no se ha identificado.');
           }
           return response()->json($data,$data['code']);
   
       }
    //delivery arrived where the customer is
    public function deliveryArrivedOnCustomerPlace(Request $request){

        //Obtengo el token
        $token = $request->header('Authorization');
   
        //Compruebo el token
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        
        //Recoger los datos por POST
        $json = $request->input('json',null);
        $params_array = json_decode($json,true);
   
   
        if($checkToken && !empty($params_array)){
        
        $delivery = $jwtAuth->checkToken($token,true);     
   
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
            
           $order = Orders::where("id",$params_array["order_id"])->get();
   
           if($order[0]->delivery_id == $delivery->sub){
   
               $update = array('shipping_status'=>4);
               $update_order = Orders::where('id',$params_array['order_id'])->update($update);

             //Notificar al delivery
              /*      define('API_ACCESS_KEY','AIzaSyBHUE3XD7onAUJbZwsAkgKc7mYbbyqkPec');
                    $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
                    $token = $delivery[0]->notification_token;
                        $notification = [
                               'title' =>'Zerdaly Business',
                               'body' => '¡'.$bs->business_name.' te solícita para un envío!',
                               'icon' =>'myIcon', 
                               'sound' => 'mySound'
                           ];
                           $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];
                   
                           $fcmNotification = [
                               //'registration_ids' => $tokenList, //multple token array
                               'to'        => $token, //single token
                               'notification' => $notification,
                               'data' => $extraNotificationData
                           ];
                   
                           $headers = [
                               'Authorization: key=' . API_ACCESS_KEY,
                               'Content-Type: application/json'
                           ];
                   
                   
                           $ch = curl_init();
                           curl_setopt($ch, CURLOPT_URL,$fcmUrl);
                           curl_setopt($ch, CURLOPT_POST, true);
                           curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                           curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                           curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
                           $result = curl_exec($ch);
                           curl_close($ch);

                           */
   
               $data = array('code'=>'200',
               'status'=>'success',
               'message'=>'Delivery llego donde el cliente exitosamente.');
   
   
           }else{
               $data = array('code'=>'400',
               'status'=>'error',
               'message'=>'Esta orden ha sido tomada por otro delivery.');
           }
         }
   
        }else{
           $data = array('code'=>'400',
           'status'=>'error',
           'message'=>'El delivery no se ha identificado.');
           }
           return response()->json($data,$data['code']);
   
       }

 
}
