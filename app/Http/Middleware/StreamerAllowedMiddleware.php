<?php

namespace App\Http\Middleware;

use Closure;

use App\User;

use App\Helpers\Helper;


class StreamerAllowedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user_details = User::find($request->id);

        if(!$user_details){

            $response = ['success' => false, 'error' => Helper::error_message(133), 'error_code' => 133];

            return response()->json($response, 200);

        }
        
        if($user_details->is_content_creator == VIEWER_STATUS) {

            $response = ['success' => false, 'error' => api_error(131), 'error_code' => 131];

            return response()->json($response, 200);
        }

        return $next($request);
    }
}
