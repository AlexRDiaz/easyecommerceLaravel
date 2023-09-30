<?php
namespace App\Http\Middleware;
use Closure;
use GuzzleHttp\Psr7\Response;

class Cors
{
  public function handle($request, Closure $next)
  {
    // return $next($request)
    //    //Url a la que se le dará acceso en las peticiones
    //    ->header("Access-Control-Allow-Origin", "*")
    //   //Métodos que a los que se da acceso
    //   ->header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE")
    //   //Headers de la petición
    //   ->header("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, X-Token-Auth, Authorization"); 
    $headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
      'Access-Control-Allow-Headers' => "Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Authorization , Access-Control-Request-Headers"
  ];
      
      if ($request->getMethod() == "OPTIONS") {
        // The client-side application can set only headers allowed in Access-Control-Allow-Headers
                    return Response::make('OK', 200, $headers);
                }
        
                $response = $next($request);
                foreach ($headers as $key => $value)
                    $response->header($key, $value);
                return $response;
            
  }
}