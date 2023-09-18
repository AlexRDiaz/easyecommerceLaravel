<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    protected $table = 'transaccion';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'tipo',
        'monto',
        'valor_actual',
        'marca_de_tiempo',
        'id_origen',
        'origen',
        'id_vendedor'
    ];
    protected $casts = [
        
    ];

    public static array $rules = [
        
    ];
}
