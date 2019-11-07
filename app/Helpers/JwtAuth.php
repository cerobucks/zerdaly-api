<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuninate\Support\Facades\DB;
use App\User;
use App\Business;
use App\Delivery;


class JwtAuth{
    
    public $key;

    public function __construct(){
        $this->key = "Lif3_1s-T0o+Sh0rt*T0@B3!W0rk1ng%F0r(Som30n3)3lse's|Dr3am?$";
    }

   public function businessLogIn($email,$password,$getToken = null){
 //Buscar si existe el negocio con sus credenciales.
    $business = Business::where([
        'email'=>$email,
        'password'=>$password
        ])->first();

    $signup = false;

    //Comprobar si son correctas(si es un objeto)
    if(is_object($business)){
        $signup = true;
    }

    //Generar el token de los datos indetificados
    if($signup){
        $token = array(
            'sub'=>$business->id,
            'email'=>$business->email,
            'business_name'=>$business->business_name,
        );

        $jwt = JWT::encode($token,$this->key,'HS256');
        $decoded = JWT::decode($jwt,$this->key,['HS256']);

    //Devolver los datos decodificados o el token, en funcion de parametro.
    if(is_null($getToken)){
        $data = array(
            'code'=>'200',
            'status'=>'success',
            'message'=>'Login correcto.',
            'token'=>$jwt);
    }else{
        $data = array(
            'code'=>'200',
            'status'=>'success',
            'message'=>'Login correcto.',
            'token_decoded'=>$decoded);
    }

    }else{
        $data = array(
            'code'=>'400',
            'status'=>'error',
            'message'=>'Login incorrecto.');
    }

    return $data;
}

    public function deliveryLogIn($email,$password,$getToken = null){
        
  //Buscar si existe el delivery con sus credenciales.
    $delivery = Delivery::where([
        'email'=>$email,
        'password'=>$password
        ])->first();

    $signup = false;

    //Comprobar si son correctas(si es un objeto)
    if(is_object($delivery)){
        $signup = true;
    }

    //Generar el token de los datos indetificados
    if($signup){
        $token = array(
            'sub'=>$delivery->id,
            'email'=>$delivery->email,
            'name'=>$delivery->name ." ".$delivery->lastname,
        );

        $jwt = JWT::encode($token,$this->key,'HS256');
        $decoded = JWT::decode($jwt,$this->key,['HS256']);

    //Devolver los datos decodificados o el token, en funcion de parametro.
    if(is_null($getToken)){
        $data = array(
            'code'=>'200',
            'status'=>'success',
            'message'=>'Login correcto.',
            'token'=>$jwt);
    }else{
        $data = array(
            'code'=>'200',
            'status'=>'success',
            'message'=>'Login correcto.',
            'token_decoded'=>$decoded);
    }

    }else{
        $data = array(
            'code'=>'400',
            'status'=>'error',
            'message'=>'Login incorrecto.');
    }

    return $data;
    }

    public function userLogIn($email,$password,$getToken = null){
        
        //Buscar si existe el usuario con sus credenciales.
          $user = User::where([
              'email'=>$email,
              'password'=>$password
              ])->first();
      
          $signup = false;
      
          //Comprobar si son correctas(si es un objeto)
          if(is_object($user)){
              $signup = true;
          }
      
          //Generar el token de los datos indetificados
          if($signup){
              $token = array(
                  'sub'=>$user->id,
                  'email'=>$user->email,
                  'name'=>$user->name ." ".$user->lastname,
              );
      
              $jwt = JWT::encode($token,$this->key,'HS256');
              $decoded = JWT::decode($jwt,$this->key,['HS256']);
      
          //Devolver los datos decodificados o el token, en funcion de parametro.
          if(is_null($getToken)){
              $data = array(
                  'code'=>'200',
                  'status'=>'success',
                  'message'=>'Login correcto.',
                  'token'=>$jwt);
          }else{
              $data = array(
                  'code'=>'200',
                  'status'=>'success',
                  'message'=>'Login correcto.',
                  'token_decoded'=>$decoded);
          }
      
          }else{
              $data = array(
                  'code'=>'400',
                  'status'=>'error',
                  'message'=>'Login incorrecto.');
          }
      
          return $data;
          }


    public function checkToken($jwt,$getIdentity = false){
        $auth = false;
    //Decodificar el token
        try{
            $jwt = str_replace('"','',$jwt);
            $decoded = JWT::decode($jwt,$this->key,['HS256']);
        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }
         //Comprobar si el token decodificado es un objeto
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


    public function getStripeToken(){
      return 'sk_test_ptAY8lawggekPbhxEGoFrdIW00DN8diyAu';
    }
}