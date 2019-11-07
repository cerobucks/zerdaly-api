<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Business;
use App\Delivery;
use App\DeliveryRequest;
use App\Category;
use App\BusinessBank;
use App\BusinessLikes;
use App\BusinessSubscription;
use App\Orders;
use App\Products;
use Carbon\Carbon;

class BusinessController extends Controller
{
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
          'owner_name'=>'required|regex:/^[\pL\s\-]+$/u',
          'business_name'=>'required|regex:/^[\pL\s\-]+$/u|unique:business',
          'email'=>'required|email|unique:business',
          'password'=>'required',
          'dob'=>'required',
          'city'=>'required',
          'phone'=>'required',
          'category_id'=>'required',
          'shipping_delay'=>'required',
          'direction_details'=>'required',
          'latitude'=>'required',
          'longitude'=>'required',
          'id_image'=>'required',
          'face_image'=>'required',

        ]);
  
        if($validate->fails()){
          //la validacion ha fallado
          $data = array('status'=>'error',
                        'code'=>404,
                        'message'=>'El usuario no se ha creado',
                        'errors'=>$validate->errors()
                      );
        }else{
        //Guardar Imagenes ID y Face
        $id_image_name = "id-image-".$params_array["business_name"]."-".time().".jpg";
        $data = base64_decode($params_array["id_image"]);
        \Storage::disk('id-images')->put($id_image_name, $data);

        $face_image_name = "face-image-".$params_array["business_name"]."-".time().".jpg";
        $data = base64_decode($params_array["face_image"]);
        \Storage::disk('face-images')->put($face_image_name, $data);

  
        //Cifrar la contrasena
        $pwd = hash('sha256',$params->password);
  
        //Crear usuario
        $business = new Business();
        $business->owner_name = $params_array["owner_name"];
        $business->business_name = $params_array["business_name"];
        $business->email = $params_array["email"];
        $business->password = $pwd;
        $business->dob = $params_array["dob"];
        $business->city = $params_array["city"];
        $business->phone = $params_array["phone"];
        $business->shipping_delay = $params_array["shipping_delay"];
        $business->category_id = $params_array["category_id"];
        $business->latitude = $params_array["latitude"];
        $business->longitude = $params_array["longitude"];
        $business->direction_details = $params_array["direction_details"];
        $business->id_image = $id_image_name;
        $business->face_image = $face_image_name;
        $business->validated = 0;
        $business->status = 0;


        //guardar el usuario
        $business->save();

        //enviar el token
        $jwtAuth = new \JwtAuth();
        $token = $jwtAuth->businessLogIn($params->email,$pwd);

        $data = array('status'=>'success',
                      'code'=>200,
                      'message'=>'El negocio se ha creado correctamente',
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
                        'message'=>'El negocio no se ha podido identificar',
                        'errors'=>$validate->errors()
                      );
        }else{
  
          //Cifrar contrasena
          $pwd = hash('sha256',$params->password);
  
          //devolver token o datos
          $signup =  $jwtAuth->businessLogIn($params->email,$pwd);
  
          if(!empty($params->getToken)){
            $signup =  $jwtAuth->businessLogIn($params->email,$pwd,true);
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
        
        $business = $jwtAuth->checkToken($token,true);

        //quitar los campos que no deseo actualizar
        unset($params_array["id"]);
        unset($params_array["owner_name"]);
        unset($params_array["password"]);
        unset($params_array["dob"]);
        unset($params_array["city"]);
        unset($params_array["category_id"]);
        unset($params_array["id_image"]);
        unset($params_array["face_image"]);
        unset($params_array["created_at"]);
       
        //actulizar negocio en DB
        $business_update = Business::where('id',$business->sub)->update($params_array);

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
        'message'=>'la imagen se ha guardado',
        'errors'=>$validate->errors()
      );

    }else{
      $image_name = "image-".$business->email."-".time().".jpg";
      $data = base64_decode($params_array["image"]);
      \Storage::disk('business-images')->put($image_name, $data);

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
       $isset = \Storage::disk('business-images')->exists($filename);

       if($isset){
         $file = \Storage::disk('business-images')->get($filename);
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
        $bs = $jwtAuth->checkToken($token,true);

          //Buscar negocio en la base de datos
          $business = Business::find($bs->sub);

          //buscar datos referentes al negocio
          $business_sales = Orders::where('business_id',$bs->sub)->get()->groupBy(function($date){
            return Carbon::parse($date->created_at)->format('Y-W');
          });

          $business_sales =  $business_sales->reverse();
          $business_orders = Orders::where('business_id',$bs->sub)->get();

          $business_products = Products::where('business_id',$bs->sub)->get();
          $business_likes = BusinessLikes::where('business_id',$bs->sub)->get();

          $data = array(
            'code'=>200,
            'status' => 'success',
            'business' => $business,
            'business_sales' => $business_sales,
            'business_orders' => $business_orders,
            'business_products' => $business_products,
            'business_likes' => $business_likes,
          );
          
       }else{
        $data = array('code'=>'400',
        'status'=>'error',
        'message'=>'El negocio no se ha identificado.');
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
        
        $bs = $jwtAuth->checkToken($token,true);
        $bank = new BusinessBank();
      
        $bank->business_id = $bs->sub;
        $bank->bank_name = $params_array["bank_name"];
        $bank->account_type = $params_array["account_type"];
        $bank->account_holder = $params_array["account_holder"];
        $bank->account_number = $params_array["account_number"];

        $bank->save();

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
      
      $business = $jwtAuth->checkToken($token,true);

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
      $bank_update = BusinessBank::where('business_id',$business->sub)->update($params_array);

      //devolver array con resultado
      $data = array('code'=>'200',
                    'status'=>'success',
                    'message'=>$business,
                    'changes'=>$params_array);

      }

      }else{
      $data = array('code'=>'400',
      'status'=>'error',
      'message'=>'El negocio no se ha identificado.');
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

        $business = $jwtAuth->checkToken($token,true);

        $bank = DeliveryBank::where('delivery_id',$business->sub)->get();

              //devolver array con resultado
              $data = array('code'=>'200',
              'status'=>'success',
              'message'=>$bank);

        }else{
          $data = array('code'=>'400',
          'status'=>'error',
          'message'=>'El negocio no se ha identificado.');
          }
          return response()->json($data,$data['code']);

        }
       
      public function newSubscription(Request $request){
       
        //Obtengo el token
        $token = $request->header('Authorization');

        //Compruebo el token
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        //Recoger los datos por POST
        $json = $request->input('json',null);
        $params_array = json_decode($json,true);

        if($checkToken && !empty($params_array)){

      //Validar datos
      $validate = \Validator::make($params_array,[
        'card_number'=>'required',
        'exp_month'=>'required',
        'exp_year'=>'required',
        'cvc'=>'required',
      ]);

      if($validate->fails()){
        $data = array(
          'code'=>'400',
          'status' => 'error',
          'message' => $validate->errors()
        );

      }else{
        $business = $jwtAuth->checkToken($token,true);

        \Stripe\Stripe::setApiKey($jwtAuth->getStripeToken());

        //card token
        try {
          $token = \Stripe\Token::create([
            'card' => [
              'number' => $params_array['card_number'],
              'exp_month' => $params_array['exp_month'],
              'exp_year' =>$params_array['exp_year'],
              'cvc' => $params_array['cvc']
            ]
          ]);
        } catch(\Stripe\Exception\CardException $e) {

        $data = array(
          'code'=>'400',
          'status'=>'error',
          'message'=>$e->getMessage()
        );
        return response()->json($data,$data['code']);
        }catch (\Stripe\Exception\RateLimitException $e) {
          // Too many requests made to the API too quickly
          $data = array(
            'code'=>'400',
            'status'=>'error',
            'message'=>$e->getMessage()
          );
          return response()->json($data,$data['code']);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
          // Invalid parameters were supplied to Stripe's API
          $data = array(
            'code'=>'400',
            'status'=>'error',
            'message'=>$e->getMessage()
          );
          return response()->json($data,$data['code']);
        } catch (\Stripe\Exception\AuthenticationException $e) {
          // Authentication with Stripe's API failed
          // (maybe you changed API keys recently)
          $data = array(
            'code'=>'400',
            'status'=>'error',
            'message'=>$e->getMessage()
          );
          return response()->json($data,$data['code']);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
          // Network communication with Stripe failed
          $data = array(
            'code'=>'400',
            'status'=>'error',
            'message'=>$e->getMessage()
          );
          return response()->json($data,$data['code']);
        } catch (\Stripe\Exception\ApiErrorException $e) {
          // Display a very generic error to the user, and maybe send
          // yourself an email
          $data = array(
            'code'=>'400',
            'status'=>'error',
            'message'=>$e->getMessage()
          );
          return response()->json($data,$data['code']);
        } catch (Exception $e) {
          // Something else happened, completely unrelated to Stripe
          $data = array(
            'code'=>'400',
            'status'=>'error',
            'message'=>$e->getMessage()
          );
          return response()->json($data,$data['code']);
        }

        //Customer
       $customer = \Stripe\Customer::create([
         'email'=> $business->email,
         'source' => $token
       ]);

       //create subscription
       $subscription = \Stripe\Subscription::create([
        'customer' => $customer->id,
        'items' => [
          [
            'plan' => 'plan_G2YccUP73G77Gz',
          ],
        ],
        'trial_end' =>strtotime("+30 days")
      ]);

      $businessSubs = new BusinessSubscription();
      $businessSubs->business_id = $business->sub;
      $businessSubs->stripe_subs_id = $subscription->id;
      $businessSubs->amount = 499;
      $businessSubs->payment_status = 'successful';
      $businessSubs->save();
      
      $array = array(
        'validated'=>1,
        'status'=>1
      );
      $businessUpdate = Business::where('id',$business->sub)->update($array);

      $data = array(
        'code'=>'200',
        'status' => 'success',
        'message' => 'El negocio se ha suscripto correctamente',
        'subscription'=>$businessSubs,
      );

      }

        }else{
          $data = array(
            'code'=>'400',
            'status' => 'error',
            'message' => 'El negocio no se ha validado correctamente'
          );
        }

        return response()->json($data,$data['code']);

}


      public function getSubscription(Request $request){

                //Obtengo el token
                $token = $request->header('Authorization');

                //Compruebo el token
                $jwtAuth = new \JwtAuth();
                $checkToken = $jwtAuth->checkToken($token);
        
                if($checkToken){
                  
                  $business = $jwtAuth->checkToken($token,true);

                  $subscription = BusinessSubscription::where('business_id',$business->sub)->get();

                  \Stripe\Stripe::setApiKey($jwtAuth->getStripeToken());

                  $details = \Stripe\Subscription::retrieve(
                    $subscription[0]->stripe_subs_id
                  );
                  
                  $info = array(
                    'start_at'=> date('d/m/Y',$details->current_period_start),
                    'end_at'=> date('d/m/Y',$details->current_period_end),
                    'status' => $details->status,
                    'trial_start'=> date('d/m/Y',$details->trial_start),
                    'trial_end'=> date('d/m/Y',$details->trial_end),
                  );

                  $data = array(
                    'code'=>'200',
                    'status'=>'success',
                    'subscription'=>$info
                  );
                  
                }else{
                  $data = array(
                    'code'=>'400',
                    'status'=>'error',
                    'message'=>'No se validado correctamente el negocio.'
                  );
                }

                return response()->json($data,$data['code']);
      }

      public function renewSubscription(Request $request){

                //Obtengo el token
                $token = $request->header('Authorization');

                //Compruebo el token
                  $jwtAuth = new \JwtAuth();
                  $checkToken = $jwtAuth->checkToken($token);
                 
                    if($checkToken){
                           
                   $business = $jwtAuth->checkToken($token,true);
         
                   $bsSubscription = BusinessSubscription::where('business_id',$business->sub)->get();
         
                  \Stripe\Stripe::setApiKey($jwtAuth->getStripeToken());
         
                  $subscriptionInfo = \Stripe\Subscription::retrieve(
                             $bsSubscription[0]->stripe_subs_id
                           );

                  //create subscription
               $subscription = \Stripe\Subscription::create([
                  'customer' => $subscriptionInfo->customer,
                  'items' => [
                    [
                      'plan' => 'plan_G2YccUP73G77Gz',
                    ],
                  ],
                ]);
                
                //Acutualizar la suscripcion en la base de datos.
                $array = array(
                  'stripe_subs_id'=>$subscription->id,
                );
                $bsSubscription = BusinessSubscription::where('business_id',$business->sub)->update($array);
 
                $array = array(
                  'status'=>1
                );
                $businessUpdate = Business::where('id',$business->sub)->update($array);
                   $data = array(
                     'code'=>'200',
                     'status'=>'success',
                     'message'=>'Suscripción actualizada correctamente.'
                   );
 
               }else{
                 $data = array(
                   'code'=>'400',
                   'status'=>'error',
                   'message'=>'No se validado correctamente el negocio.'
                 );
               }
 
               return response()->json($data,$data['code']);


      }

      public function cancelSubscription(Request $request){

                //Obtengo el token
                 $token = $request->header('Authorization');

               //Compruebo el token
                 $jwtAuth = new \JwtAuth();
                 $checkToken = $jwtAuth->checkToken($token);
                
                   if($checkToken){
                          
                  $business = $jwtAuth->checkToken($token,true);
        
                  $bsSubscription = BusinessSubscription::where('business_id',$business->sub)->get();
        
                 \Stripe\Stripe::setApiKey($jwtAuth->getStripeToken());
        
                 $subscription = \Stripe\Subscription::retrieve(
                            $bsSubscription[0]->stripe_subs_id
                          );
                 
                  $subscription->cancel();
                  $array = array(
                    'validated'=>1,
                    'status'=>0
                  );
                  $businessUpdate = Business::where('id',$business->sub)->update($array);

                  $data = array(
                    'code'=>'200',
                    'status'=>'success',
                    'message'=>'Suscripción cancelada correctamente.'
                  );

              }else{
                $data = array(
                  'code'=>'400',
                  'status'=>'error',
                  'message'=>'No se validado correctamente el negocio.'
                );
              }

              return response()->json($data,$data['code']);
      }
      
      public function getDeliveriesAvailible(Request $request){
           
             //Obtengo el token
             $token = $request->header('Authorization');

             //Compruebo el token
             $jwtAuth = new \JwtAuth();
             $checkToken = $jwtAuth->checkToken($token);
                        
             if($checkToken){
                                  
             $bs = $jwtAuth->checkToken($token,true);
              
             $business = Business::where('id',$bs->sub)->get();
             
             $where = array(
              'status'=>1,
              'validated'=>1,
              'city'=> $business[0]->city
             );
             $deliveries = Delivery::where($where)->get();

             $data = array(
               'code'=>'200',
               'status'=>'success',
               'deliveries'=>$deliveries
             );
             

             }else{
               $data = array(
                 'code'=>'400',
                 'status'=>'error',
                 'message'=>'El negocio no se ha validado correctamente.'
               );
             }

             return response()->json($data,$data['code']);

      }
      

      public function contactDelivery(Request $request){

                //Obtengo el token
                $token = $request->header('Authorization');

                //Compruebo el token
                $jwtAuth = new \JwtAuth();
                $checkToken = $jwtAuth->checkToken($token);
        
                //Recoger los datos por POST
                $json = $request->input('json',null);
                $params_array = json_decode($json,true);
        
                if($checkToken && !empty($params_array)){

                  $validate = \Validator::make($params_array,[
                    'delivery_id'=>'required',
                    'order_id'=>'required'
                  ]);

                  if($validate->fails()){
                    $data = array(
                      'code'=>'400',
                      'status'=>'error',
                      'message'=>$validate->errors()
                    );

                  }else{

                    $bs = $jwtAuth->checkToken($token,true);

                   //Compruebo si el delivery ha sido contactado
                   $where = array(
                     'business_id'=>$bs->sub,
                     'delivery_id'=>$params_array["delivery_id"],
                     'order_id'=>$params_array["order_id"],

                   );
                   $hasBeenContacted = DeliveryRequest::where($where)->get();

                   if(isset($hasBeenContacted[0]->id)){
                    $data = array(
                      'code'=>'400',
                      'status'=>'error',
                      'message'=>'El delivery ya ha sido contactado.'
                    );
                   }else{
                    //Compruebo si el delivery esta en otro envio.
                    $delivery = Delivery::where('id',$params_array["delivery_id"])->get();
                    
                    if(isset($delivery[0]) && $delivery[0]->status == 0){
                      $data = array(
                        'code'=>'400',
                        'status'=>'error',
                        'message'=>'El delivery ya esta en otro envío.'
                      );
                    }else{
                  
                    //Contacto al delivery
                    $deliveryRequest = new DeliveryRequest();
                    $deliveryRequest->business_id = $bs->sub;
                    $deliveryRequest->delivery_id = $params_array["delivery_id"];
                    $deliveryRequest->order_id = $params_array["order_id"];
                    $deliveryRequest->status = 0;
                    $deliveryRequest->save();

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

                    $data = array(
                      'code'=>'200',
                      'status'=>'success',
                      'message'=>'El delivery ha sido contactado.'
                    );
                    }

                   }
                    
                  }

                }else{
                  $data = array(
                    'code'=>'400',
                    'status'=>'error',
                    'message'=>'El negocio no se ha validado correctamente.'
                  );
                }
                return response()->json($data,$data['code']);
      }


    }