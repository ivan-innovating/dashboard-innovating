<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {

        if(Auth::check() && Auth::user()->is_ban)
        {

            $erased = Auth::user()->is_erased; // "1"= user is erased / "0"= user is not erased
            if ($erased == 1) {
                Auth::logout();
                return redirect()->back()
                    ->withErrors(['email' => 'Your account has been deleted from our systems. Please contact administrator in info@innovating.works.'])
                ;
            }

            $banned = Auth::user()->is_ban; // "1"= user is banned / "0"= user is unBanned

            if ($banned == 1) {
                Auth::logout();
                $message = 'Your account has been Banned. Please contact administrator in info@innovating.works.';
                return redirect()->route('login')
                    ->with('status',$message)
                    ->withErrors(['email' => 'Your account has been Banned. Please contact administrator in info@innovating.works.'])
                ;
            }
        }
        return $next($request);
    }
}
