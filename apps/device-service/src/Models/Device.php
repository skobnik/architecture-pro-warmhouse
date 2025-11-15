<?php

namespace DeviceService\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = [
        'name', 
        'type', 
        'location', 
        'ip_address', 
        'status', 
        'value'
    ];
    
    protected $casts = [
        'value' => 'float'
    ];
}