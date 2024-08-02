<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnvioMailUsuariosController extends Controller
{
    //
    public function enviarEmailsUsuarios(){

        $usersentidad = \App\Models\UsersEntidad::all();
        $sinverificar = \App\Models\User::whereNull('email_verified_at')->count();
        $total = \App\Models\User::all()->count();

        foreach($usersentidad as $user){
            if($user->user !== null){
                if($user->entidad !== null){
                    $usuarios[$user->user->id] = $user->user->email." (".$user->entidad->Nombre.")";
                }                           
            }
            if($user->entidad !== null){
                $empresas[$user->entidad->id] = $user->entidad->Nombre;
            }
        }

        $correos = \App\Models\SuperAdminEmails::where('Estado', 'pendiente')->get();

        return view('admin.emailssuperadmin.emailssuperadmin', [
            'usuarios' => $usuarios,       
            'empresas' => $empresas,     
            'total' => $total,
            'sinverificar' => $sinverificar,
            'correos' => $correos
        ]);
    }

    public function crearEmailsUsuarios(Request $request){

        if($request->get('todos') !== null){
        
            \App\Models\User::chunk(200, function (Collection $users) use ($request){
                $emails = array();
                foreach($users as $user){
                    $emails[] = $user->email;
                }

                try{

                    $mail_admin = new \App\Models\SuperAdminEmails();
                    $mail_admin->todos_usuarios = 1;
                    $mail_admin->usuarios = json_encode($emails);
                    $mail_admin->asunto_mail = $request->get('asunto');
                    $mail_admin->cabecera_mail = $request->get('cabecera');
                    $mail_admin->cuerpo_mail =$request->get('cuerpo');
                    $mail_admin->pie_mail = $request->get('pie');
                    $mail_admin->url_innovating = ($request->get('url') == "") ? null : $request->get('url');
                    $mail_admin->creator_id = Auth::user()->id;
                    $mail_admin->save();
                }catch(Exception $e){
                    Log::error($e->getMessage());
                    return redirect()->back()->withErrors('No se ha podido generar el mail, 001');
                }
            });

        }else{

            if($request->get('sinempresa') !== null){
                \App\Models\User::whereNull('email_verified_at')->chunk(200, function (Collection $users) use ($request) {
                    $emails = array();
                    foreach($users as $user){
                        $emails[] = $user->email;
                    }
                    try{
                        $mail_admin = new \App\Models\SuperAdminEmails();
                        $mail_admin->usuarios_sinverificar = 1;
                        $mail_admin->usuarios = json_encode($emails);
                        $mail_admin->empresas = null;
                        $mail_admin->asunto_mail = $request->get('asunto');
                        $mail_admin->cabecera_mail = $request->get('cabecera');
                        $mail_admin->cuerpo_mail =$request->get('cuerpo');
                        $mail_admin->pie_mail = $request->get('pie');
                        $mail_admin->url_innovating = ($request->get('url') == "") ? null : $request->get('url');
                        $mail_admin->creator_id = Auth::user()->id;
                        $mail_admin->save();
                    }catch(Exception $e){
                        Log::error($e->getMessage());
                        return redirect()->back()->withErrors('No se ha podido generar el mail, 001');
                    }
                });
            }

            if($request->get('usuarios') !== null){

                $usuarios = array_chunk($request->get('usuarios'), 200);
                $empresas = array();
                if($request->get('empresas') !== null){
                    foreach($request->get('empresas') as $id){
                        $entity = \App\Models\Entidad::find($id);
                        $empresas[$entity->id] = $entity->Nombre;
                    }
                }

                foreach($usuarios as $ids){

                    $emails = array();
                    foreach($ids as $id){
                        $user = \App\Models\User::find($id);
                        if($user){
                            $emails[] = $user->email;
                        }
                    }
                    
                    if(!empty($emails)){
                        try{
                            $mail_admin = new \App\Models\SuperAdminEmails();
                            $mail_admin->usuarios_sinverificar = ($request->get('sinempresa') !== null) ? 1 : 0;
                            $mail_admin->usuarios = json_encode($emails);
                            $mail_admin->empresas =  json_encode($empresas);
                            $mail_admin->asunto_mail = $request->get('asunto');
                            $mail_admin->cabecera_mail = $request->get('cabecera');
                            $mail_admin->cuerpo_mail =$request->get('cuerpo');
                            $mail_admin->pie_mail = $request->get('pie');
                            $mail_admin->url_innovating = ($request->get('url') == "") ? null : $request->get('url');
                            $mail_admin->creator_id = Auth::user()->id;
                            $mail_admin->save();
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                            return redirect()->back()->withErrors('No se ha podido generar el mail, 001');
                        }
                    }
                }

            }

        }

        return redirect()->back()->withSuccess('Generado el mail correctamente y añadido a la cola de correos a enviar');
    }

    public function editarMail($id){
        
        try{
            $correo = \App\Models\SuperAdminEmails::find($id);   
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha encontrado el mail para editar, 001');
        }

        return view('admin.emailssuperadmin.editar', [
            'correo' => $correo
        ]);
    }

    public function editMail(Request $request){

        try{
            \App\Models\SuperAdminEmails::find($request->get('id'))->update(
                [
                    'asunto_mail' => $request->get('asunto'),
                    'cabecera_mail' => $request->get('cabecera'),
                    'cuerpo_mail' => $request->get('cuerpo'),
                    'pie_mail' => $request->get('pie'),
                    'url_innovating' => ($request->get('url') == "") ? null : $request->get('url'),
                    'creator_id' => Auth::user()->id
                    //'updated_at' => Carbon::now();
                ]
            );            
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha editar el mail, 001');
        }

        return redirect()->back()->withSuccess('Se ha editado correctamente el mail');
    }

    public function enviarMailPrueba(Request $request){

        try{
            $correo = \App\Models\SuperAdminEmails::find($request->get('id'));  
            $mail = new \App\Mail\SuperAdminMail($correo);
            Mail::to(ltrim(Auth::user()->email))->queue($mail);
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido enviar el correo de prueba a '.Auth::user()->email.' 001');
        }

        return redirect()->back()->withSuccess('Correo de prueba enviado a '.Auth::user()->email);
    }

    public function getUsuariosEntidad(Request $request){
        
        $usuarios = array();

        foreach($request->get('id') as $id){

            $usersentidad = \App\Models\UsersEntidad::where('entidad_id', $id)->get();           
            foreach($usersentidad as $user){
                $user->user->role = $user->role;
                $user->user->empresanombre = $user->entidad->Nombre;
                $usuarios[] = $user->user;
            }
        }

        return response()->json(json_encode($usuarios));
    }

    public function deleteMail($id){

        try{
            \App\Models\SuperAdminEmails::find($id)->delete();            
        }catch(Exception $e){
            Log::error($e->getMessage());
            return redirect()->back()->withErrors('No se ha podido borrar el mail, 001');
        }

        return redirect()->back()->withSuccess('Se ha borrado correctamente el mail');
    }
}
