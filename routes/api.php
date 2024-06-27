<?php

use App\Http\Controllers\V1\EntityController;
use App\Http\Controllers\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/threads',function(Request $request){

    $userId = $request->user()->id;

    $filterType = $request->input('filter_type','all');
    $filterValue = $request->input('filter_value');

    $threads = collect();
    if($filterType == 'encajes'){
        $threads = DB::table('messages_threads_users as tu')
            ->join('messages_threads as t', 'tu.thread_id', '=', 't.id')
            ->join('messages_threads_entidads as mte', 'tu.thread_id', '=', 'mte.messages_threads_id')
            ->leftJoin('messages_threads_messages as m', 't.last_message_id', '=', 'm.id')
            ->join('Encajes_zoho as e','t.encaje_id','=','e.id')
            ->join('entidades as en','mte.entidad_id','=','en.id')
            ->join('proyectos as pr','pr.id','=','e.Proyecto_id')
            //->select('t.*', 'tu.unread_messages', 'm.message')
            ->select('t.id','e.Titulo as title','t.updated_at', 'tu.unread_messages', 'm.message', 'e.tipoPartner','e.naturalezaPartner','e.Encaje_presupuesto','en.Nombre','pr.esAnonimo', 'pr.empresaPrincipal')
            ->where('tu.user_id',$userId)
            ->where('t.encaje_id',$filterValue)
            ->where('mte.entidad_id', '!=', userEntidadSelected()->id)
            ->get();

    }elseif ($filterType == 'proyectos'){
        $threads = DB::table('messages_threads_users as tu')
            ->join('messages_threads as t', 'tu.thread_id', '=', 't.id')
            ->join('messages_threads_entidads as mte', 'tu.thread_id', '=', 'mte.messages_threads_id')
            ->leftJoin('messages_threads_messages as m', 't.last_message_id', '=', 'm.id')
            ->join('Encajes_zoho as e','t.encaje_id','=','e.id')
            ->join('entidades as en','mte.entidad_id','=','en.id')
            ->join('proyectos as pr','pr.id','=','e.Proyecto_id')
            //->select('t.*', 'tu.unread_messages', 'm.message')
            ->select('t.id','e.Titulo as title','t.updated_at', 'tu.unread_messages', 'm.message', 'e.tipoPartner','e.naturalezaPartner','e.Encaje_presupuesto','en.Nombre','pr.esAnonimo', 'pr.empresaPrincipal')
            ->where('tu.user_id',$userId)
            ->where('e.proyecto_id',$filterValue)
            ->where('mte.entidad_id', '!=', userEntidadSelected()->id)
            ->get();
    }else{
        //Obtengo los hilos de este usuario
        $threads = DB::table('messages_threads_users as tu')
            ->join('messages_threads as t', 'tu.thread_id', '=', 't.id')
            ->join('messages_threads_entidads as mte', 'tu.thread_id', '=', 'mte.messages_threads_id')
            ->leftJoin('messages_threads_messages as m', 't.last_message_id', '=', 'm.id')
            ->join('Encajes_zoho as e','t.encaje_id','=','e.id')
            ->join('entidades as en','mte.entidad_id','=','en.id')
            ->join('proyectos as pr','pr.id','=','e.Proyecto_id')
            //->select('t.*', 'tu.unread_messages', 'm.message')
            ->select('t.id','e.Titulo as title','t.updated_at', 'tu.unread_messages', 'm.message','e.tipoPartner','e.naturalezaPartner','e.Encaje_presupuesto','en.Nombre','pr.esAnonimo', 'pr.empresaPrincipal')
            ->where('tu.user_id',$userId)
            ->where('mte.entidad_id', '!=', userEntidadSelected()->id)
            ->get();
    }

    foreach($threads as $thread){
        $thread->format_presupuesto = number_shorten($thread->Encaje_presupuesto, 0);
    }

    #dump(userEntidadSelected()->id);
    #dd($userId);
    return response()->json($threads);
})->middleware('auth:sanctum')->middleware(['checkRole:Manager|Admin|SuperAdmin'])->name('api.threads');

Route::get('/threads/{thread_id}',function(Request $request,\App\Models\MessagesThread $thread_id){

    $userId = $request->user()->id;

    $threadInfo = DB::table('messages_threads_users as tu')
        ->join('messages_threads as t', 'tu.thread_id', '=', 't.id')
        ->leftJoin('messages_threads_messages as m', 't.last_message_id', '=', 'm.id')
        ->join('Encajes_zoho as e','t.encaje_id','=','e.id')
        ->join('proyectos as pr','pr.id','=','e.Proyecto_id')
        //->select('t.*', 'tu.unread_messages', 'm.message')
        ->select('t.id','e.Titulo as title','t.updated_at', 'tu.unread_messages', 'm.message', 'pr.esAnonimo', 'pr.empresaPrincipal')
        ->where('tu.thread_id',$thread_id->id)
        ->where('tu.user_id',$userId)
        ->first();

    return response()->json($threadInfo);
})->middleware('auth:sanctum')->middleware(['checkRole:Manager|Admin|SuperAdmin'])->name('api.threads.thread_id');

Route::get('/threads/{thread_id}/messages',function(Request $request,\App\Models\MessagesThread $thread_id){

    $messages = \App\Models\MessagesThreadsMessage::with('user')->where('thread_id',$thread_id->id)->orderBy('created_at')->get();

    return response()->json($messages);
})->middleware('auth:sanctum')->middleware(['checkRole:Manager|Admin|SuperAdmin'])->name('api.threads.messages');

Route::post('/threads/{thread_id}/read',function(Request $request,\App\Models\MessagesThread $thread_id){

    $userId = $request->user()->id;

    \App\Models\MessagesThreadsUser::where('thread_id',$thread_id->id)->where('user_id',$userId)->update(['unread_messages' => 0]);

    return response()->json(true);
})->middleware('auth:sanctum')->middleware(['checkRole:Manager|Admin|SuperAdmin'])->name('api.threads.read');

Route::post('/messages',function(Request $request){

    $userId = $request->user()->id;

    $thread_id = $request->input('thread_id');

    $message = $request->input('message');

    $messageModel = new \App\Models\MessagesThreadsMessage();
    $messageModel->thread_id = $thread_id;
    $messageModel->user_id = $userId;
    $messageModel->message = $message;
    $messageModel->save();

    //actualizo el Thread
    $threadModel = \App\Models\MessagesThread::find($thread_id);
    $threadModel->last_message_id = $messageModel->id;
    $threadModel->save();

    //actualizo los unread Message Thread User
    $userThreads = \App\Models\MessagesThreadsUser::where('thread_id',$thread_id)->where('user_id','!=',$userId)->get();
    foreach ($userThreads as $userThread) {
        $userThread->unread_messages = $userThread->unread_messages+1;
        $userThread->save();
    }

    $returnMessage = \App\Models\MessagesThreadsMessage::with('user')->find($messageModel->id);

    $checkentities = \App\Models\MessagesThreadsEntidad::where('messages_threads_id', $thread_id)->get();
    foreach($checkentities as $entity){
        if($entity->type == "lider"){               
            $lider = $entity->entidad_id;
        }elseif($entity->type == "participante"){
            $participante = $entity->entidad_id;
        }
    }

    if(isset($participante) && isset($lider)){
        $lastMessage = \App\Models\MessagesThreadLast::where('id_encaje', $threadModel->encaje_id)->where('entity_principal', $lider)->where('entity_participante', $participante)->first();
    
        if($lastMessage){
            $lastMessage->id_ultimo_mensaje = $messageModel->id;
            $lastMessage->save();
        }else{        
            $lastMessage = new \App\Models\MessagesThreadLast();
            $lastMessage->id_encaje = $threadModel->encaje_id;
            $lastMessage->entity_principal = $lider;
            $lastMessage->entity_participante = $participante;
            $lastMessage->id_ultimo_mensaje = $messageModel->id;
            $lastMessage->save();
        }
    }

    //TODO: Enviar evento
    \App\Events\ThreadStatusUpdated::dispatch($thread_id,$userId);

    \App\Events\NewMessage::dispatch($messageModel);

    return response()->json($returnMessage);
})->middleware('auth:sanctum')->middleware(['checkRole:Manager|Admin|SuperAdmin'])->name('api.messages');

Route::prefix('v1')->group(function () {
    //Prefijo V1, todo lo que este dentro de este grupo se accedera escribiendo v1 en el navegador, es decir /api/v1/*    
    Route::group(['middleware' => 'auth.global.api'], function () {

        Route::post('register', [AuthController::class, 'register']);                
        Route::post('update', [AuthController::class, 'update']);        
        Route::post('getcompanydata/{id}', [EntityController::class, 'show']);
        #Route::post('getcompanydata/{id}', [EntityController::class, 'show']);      
        Route::get('/check/{user}', [AuthController::class, 'checkUser'])->middleware(['isBanUser'])->name('checkuser');
    });
    

    /*Route::group(['middleware' => ['auth:api']], function() {
        //Todo lo que este dentro de este grupo requiere verificación de usuario.                       
        #Route::post('get-user', [AuthController::class, 'getUser']);
        
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('details', [AuthController::class, 'getUser']);
        Route::get('logapi', [AuthController::class, 'logApi']);
    });*/
});

