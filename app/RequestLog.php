<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{

    protected $table = "request_logs";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip', 'route_name', 'route_path', 'request', 'request_headers', 'response_headers', 'response_status', 'response'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'user_id'
    ];

    public function permissions() {
        return $this->belongsToMany(Permission::class,'roles_permissions');
    }

    public function user(){
        return $this->belongsTo(User::class, 'users');
    }
}
