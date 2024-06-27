<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'isban',
        'acepto_condiciones',
        'fecha_acepta_condiciones',
        'email_verified_at',
        'cargo',
        'id_pidi'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'super_admin' => 'boolean',
        'emails_unsubscribed' => 'array'
    ];

    /*public function isAdministrator()
    {
       return $this->hasRole('SuperAdmin');
    }

    public function isEmpresa()
    {
       return $this->hasRole('Empresa');
    }

    public function isAdminEmpresa()
    {
       return $this->hasRole('Admin');
    }

    public function isManagerEmpresa()
    {
       return $this->hasRole('Manager');
    }

    public function isTecnicoEmpresa()
    {
       return $this->hasRole('Tecnico');
    }*/

    public function isBan()
    {
       return $this->is_ban;
    }

    public function isEraed()
    {
       return $this->is_erased;
    }

    public function consultorias(){
        return $this->hasMany(\App\Models\UsersConsultoria::class);
    }

    public function entidades(){
        return $this->belongsToMany(\App\Models\Entidad::class,'users_entidades','users_id')->withPivot(['role']);
    }

    public function investigador(){
        return $this->belongsTo(Investigadores::class,'investigador_id','id');
    }

    public function userdepartamentos(){
        if(userEntidadSelected() && isset(userEntidadSelected()->simulada) && userEntidadSelected()->simulada !== null && userEntidadSelected()->simulada == 1){
            return $this->hasMany(\App\Models\EntidadesDepartamentosUsers::class,'id_user','id');
        }elseif(userEntidadSelected()){
            return $this->hasMany(\App\Models\EntidadesDepartamentosUsers::class,'id_user','id')->where('id_entidad', userEntidadSelected()->id);
        }
        return $this->hasMany(\App\Models\EntidadesDepartamentosUsers::class,'id_user','id');
    }


}
