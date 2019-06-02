<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RequestLog extends Model
{

    protected $table = "request_logs";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ip', 'server_url', 'user_id', 'route_path', 'request', 'request_method', 'response_status', 'response'
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

    // Returns all the columns in the table
    public function getTableColumns(){
        return Schema::getColumnListing($this->getTable());
    }
}
