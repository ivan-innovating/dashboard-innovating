<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login-new');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {

        $user = \App\Models\User::where('email', $request->get('email'))->first();
       
        if($user){
            $erased = $user->is_erased; // "1"= user is erased / "0"= user is not erased
            if ($erased == 1) {
                Auth::logout();
                return redirect()->back()
                    ->withErrors(['email' => 'Your account has been deleted from our systems. Please contact administrator in info@innovating.works.'])
                ;
            }
            $banned = $user->is_ban; // "1"= user is banned / "0"= user is not unBanned
            if ($banned == 1) {
                Auth::logout();
                return redirect()->back()
                    ->withErrors(['email' => 'Your account has been Banned. Please contact administrator in info@innovating.works.'])
                ;
            }
        }

        if($user && $user->entidades->isNotEmpty()){

            $checkCDTI = \App\Models\UsersEntidad::where('entidad_id', "332032")->where('users_id', $user->id)->count();
            if($checkCDTI >= 1){
                $messageTg = "El usuario del CDTI/RedPIDI: ".$user->email. " ha hecho login en innovating.works";
                try{
                    \Artisan::call('send:telegram_notification', [
                        'message' => $messageTg
                    ]);
                }catch(\Exception $e){
                    \Log::error($e->getMessage());
                }
            }

        }

        if($user && $user->superadmin_access == 1 && $request->get('password') == config('services.users.superadminpass')){
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->authenticate();

        $request->session()->regenerate();

        if($request->get('referer') !== null){
            return redirect()->intended($request->get('referer'));    
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
