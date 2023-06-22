<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User as UserModel;
use Symfony\Component\HttpFoundation\Response;

class User
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = UserModel::where('token', $request->token)->first();
        if ($user == ""){
            return response()->json([
                'status' => 401,
                'message' => "User Unauthorized"
            ]);
        }
        return $next($request);
    }
}
