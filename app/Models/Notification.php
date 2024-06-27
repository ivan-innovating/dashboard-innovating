<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Notification extends Model
{
    use HasFactory;

    protected $casts = [
        'unread' => 'boolean',
    ];

    public function sendNotification($notificationType = NULL,$message = NULL,
    $usersId = NULL, $data = NULL, $companyId = 0){

        if($notificationType !== NULL && $message !== NULL && $usersId !== NULL){
            $send = false;
            //Paso 1: Añado la notificacion de la plataforma

            $notification = new \App\Models\Notification();
            $notification->type = $notificationType;
            $notification->users_id = $usersId;
            $notification->entity_id = $companyId;
            $notification->message = $message;
            $notification->data = json_encode($data);
            $notification->unread = 1;
            $notification->save();

            //Paso 2: Enviamos Mail al usuario
            $allowSend = FALSE;

            $userInfo = \App\Models\User::find($usersId);
            if(empty($userInfo->emails_unsubscribed)){
                //Si no tiene ningun email unsubscribed enviamos mail
                $allowSend = TRUE;
            }else{
                //si es una array pero no se encuentra en el listado tambien lo enviamos
                if(is_array($userInfo->emails_unsubscribed) && !in_array($notificationType,$userInfo->emails_unsubscribed)){
                    $allowSend = TRUE;
                }
            }

            if($send === true){
                if($allowSend === TRUE){
                    /*$mail = new \App\Mail\NotificationEmail($message);
                    Mail::to($userInfo->email)->queue($mail);*/
                }
            }

        }
    }
}
