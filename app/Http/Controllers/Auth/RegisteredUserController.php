<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Http\Settings\GeneralSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class RegisteredUserController extends Controller
{

    const TLDS = ['gmail','outlook','msn','yahoo','hotmail','duck.com'];

    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $allowRegister = $this->getAllowRegister();

        if(isset($allowRegister) && !empty($allowRegister) && $allowRegister == "1"){
            if($request->query('mail') !== null && $request->query('id') !== null){
                $eszoho = \App\Models\ZohoMails::where('id', $request->query('id'))->whereJsonContains('emails', $request->query('mail'))->first();
                if($eszoho){
                    return view('auth.register')->with('mail', $request->query('mail'))->with('id', $request->query('id'));
                }
            }

            return view('auth.register-new');

        }else{
            return view('auth.register_no_available');
        }
    }

    public function create_invitation(Request $request)
    {
        return view('auth.register_invitation');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {

        $request->validate([
            'nombre' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9À-ÿ ]+$/'],
            'email' => ['required', 'string', 'email', 'max:80', 'unique:users'],
            'accept_terms' => ['required'],
            'accept_rgpd' => ['required'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $zoho = 0;

        if($request->get('idzb') !== null){

            $zoho = 1;
            $zohomail = \App\Models\ZohoMails::where('id', $request->get('idzb'))->whereJsonContains('emails', $request->email)->first();
            if(!$zohomail){
                return redirect()->back()->withErrors('No coincide el correo con la solicitud');
            }

            $user = User::create([
                'name' => $request->nombre,
                'email' => $request->email,
                'acepto_condiciones' => 1,
                'fecha_acepta_condiciones' => Carbon::now(),
                'password' => Hash::make($request->password),
            ]);

            $entity = \App\Models\Entidad::where('CIF', $zohomail->Cif)->first();

            if($entity){
                $user_entity = new \App\Models\UsersEntidad;
                $user_entity->role = 'manager';
                $user_entity->entidad_id = $entity->id;
                $user_entity->users_id = $user->id;
                $user_entity->save();
            }

            $message = "Se ha registrado un nuevo usuario en innovating desde enlace BEAGLE con el nombre: ".$request->nombre." y con el email: ".$request->email;

            try{
                Artisan::call('send:telegram_notification', [
                    'message' =>  $message
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
            }

            event(new Registered($user));
            Auth::login($user);

        }

        if($zoho == 0){

            $user = User::create([
                'name' => $request->nombre,
                'email' => $request->email,
                'acepto_condiciones' => 1,
                'fecha_acepta_condiciones' => Carbon::now(),
                'password' => Hash::make($request->password),
            ]);

            $domain = explode('@', $request->email);
            $domain = explode('.', $domain[1]);

            $url = url()->previous();
            $route = app('router')->getRoutes($url)->match(app('request')->create($url))->getName();

            if(in_array($domain[0], self::TLDS) !== false && $route == "register"){
                return Redirect::route('validacorreo',  [
                    'name' => $request->nombre,
                    'email' => $request->email,
                    'pass' => $request->password
                ]);
            }

            event(new Registered($user));

            $message = "Se ha registrado un nuevo usuario en innovating con el nombre: ".$request->nombre." y con el email: ".$request->email;

            if($request->get('invitation') !== null){
                $entidadinvitacion = \App\Models\UsersEntidad::where('users_id', $user->id)->orderBy('created_at', 'DESC')->first();
                $message .= " desde el correo de invitación a la empresa, ".$entidadinvitacion->entidad->Nombre;
            }

            try{
                Artisan::call('send:telegram_notification', [
                    'message' =>  $message
                ]);
            }catch(Exception $e){
                Log::error($e->getMessage());
            }

            Auth::login($user);

        }

        return redirect(RouteServiceProvider::HOME);
    }

    function getAllowRegister(): string{
        return app(GeneralSettings::class)->allow_register;
    }

}
