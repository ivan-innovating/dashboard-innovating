<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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

    public function totalEntidades(){
        return $this->hasMany(\App\Models\Entidad::class,'id','users_entidades');
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
