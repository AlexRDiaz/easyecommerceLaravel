<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PedidosShopify
 * 
 * @property int $id
 * @property string|null $numero_orden
 * @property string|null $direccion_shipping
 * @property string|null $nombre_shipping
 * @property string|null $telefono_shipping
 * @property string|null $precio_total
 * @property string|null $observacion
 * @property string|null $ciudad_shipping
 * @property string|null $estado_interno
 * @property string|null $id_comercial
 * @property string|null $producto_p
 * @property string|null $producto_extra
 * @property string|null $cantidad_total
 * @property string|null $status
 * @property string|null $estado_logistico
 * @property string|null $ruta
 * @property string|null $name_comercial
 * @property string|null $marca_tiempo_envio
 * @property string|null $fecha_entrega
 * @property string|null $comentario
 * @property string|null $tipo_pago
 * @property string|null $archivo
 * @property string|null $estado_pagado
 * @property string|null $url_pagado_foto
 * @property string|null $estado_pago_logistica
 * @property string|null $url_p_l_foto
 * @property string|null $estado_devolucion
 * @property string|null $tienda_temporal
 * @property string|null $marca_t_d
 * @property string|null $marca_t_d_t
 * @property string|null $marca_t_d_l
 * @property string|null $marca_t_i
 * @property string|null $do
 * @property string|null $dt
 * @property string|null $dl
 * @property string|null $fecha_confirmacion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by_id
 * @property int|null $updated_by_id
 * @property string|null $comentario_rechazado
 * @property bool|null $revisado
 * 
 * @property AdminUser|null $admin_user
 * @property Collection|Novedade[] $novedades
 * @property Collection|PedidosShopifiesOperadoreLink[] $pedidos_shopifies_operadore_links
 * @property Collection|PedidosShopifiesPedidoFechaLink[] $pedidos_shopifies_pedido_fecha_links
 * @property Collection|PedidosShopifiesRutaLink[] $pedidos_shopifies_ruta_links
 * @property Collection|PedidosShopifiesSubRutaLink[] $pedidos_shopifies_sub_ruta_links
 * @property Collection|PedidosShopifiesTransportadoraLink[] $pedidos_shopifies_transportadora_links
 * @property Collection|ProductoShopifiesPedidosShopifyLink[] $producto_shopifies_pedidos_shopify_links
 * @property Collection|UpUsersPedidosShopifiesLink[] $up_users_pedidos_shopifies_links
 *
 * @package App\Models
 */
class PedidosShopify extends Model
{
	protected $table = 'pedidos_shopifies';

	protected $casts = [
		'created_by_id' => 'int',
		'updated_by_id' => 'int',
		'revisado' => 'bool'
	];

	protected $fillable = [
		'numero_orden',
		'direccion_shipping',
		'nombre_shipping',
		'telefono_shipping',
		'precio_total',
		'observacion',
		'ciudad_shipping',
		'estado_interno',
		'id_comercial',
		'producto_p',
		'producto_extra',
		'cantidad_total',
		'status',
		'estado_logistico',
		'ruta',
		'name_comercial',
		'marca_tiempo_envio',
		'fecha_entrega',
		'comentario',
		'tipo_pago',
		'archivo',
		'estado_pagado',
		'url_pagado_foto',
		'estado_pago_logistica',
		'url_p_l_foto',
		'estado_devolucion',
		'tienda_temporal',
		'marca_t_d',
		'marca_t_d_t',
		'marca_t_d_l',
		'marca_t_i',
		'do',
		'dt',
		'dl',
		'fecha_confirmacion',
		'created_by_id',
		'updated_by_id',
		'comentario_rechazado',
		'revisado'
	];

	public function admin_user()
	{
		return $this->belongsTo(AdminUser::class, 'updated_by_id');
	}

	public function novedades()
	{
		return $this->belongsToMany(Novedade::class, 'novedades_pedidos_shopify_links', 'pedidos_shopify_id', 'novedad_id')
					->withPivot('id', 'novedad_order');
	}

	public function pedidos_shopifies_operadore_links()
	{
		return $this->hasMany(PedidosShopifiesOperadoreLink::class);
	}

	public function pedidos_shopifies_pedido_fecha_links()
	{
		return $this->hasMany(PedidosShopifiesPedidoFechaLink::class);
	}

	public function pedidos_shopifies_ruta_links()
	{
		return $this->hasMany(PedidosShopifiesRutaLink::class);
	}

	public function pedidos_shopifies_sub_ruta_links()
	{
		return $this->hasMany(PedidosShopifiesSubRutaLink::class);
	}

	public function pedidos_shopifies_transportadora_links()
	{
		return $this->hasMany(PedidosShopifiesTransportadoraLink::class);
	}

	public function producto_shopifies_pedidos_shopify_links()
	{
		return $this->hasMany(ProductoShopifiesPedidosShopifyLink::class);
	}

	public function up_users_pedidos_shopifies_links()
	{
		return $this->hasMany(UpUsersPedidosShopifiesLink::class);
	}

	public function operadore()
    {
        return $this->hasManyThrough(Operadore::class, PedidosShopifiesOperadoreLink::class, 'pedidos_shopify_id', 'id', 'id', 'operadore_id');
    }
	public function transportadora()
    {
        return $this->hasManyThrough(Transportadora::class, PedidosShopifiesTransportadoraLink::class, 'pedidos_shopify_id', 'id', 'id', 'transportadora_id');
    }
	public function pedidoFecha()
    {
        return $this->hasManyThrough(PedidoFecha::class, PedidosShopifiesPedidoFechaLink::class, 'pedidos_shopify_id', 'id', 'id', 'pedido_fecha_id');
    }
	public function users()
    {
        return $this->hasManyThrough(UpUser::class, UpUsersPedidosShopifiesLink::class, 'pedidos_shopify_id', 'id', 'id', 'user_id');
    }

	public function ruta()
    {
        return $this->hasManyThrough(Ruta::class, PedidosShopifiesRutaLink::class, 'pedidos_shopify_id', 'id', 'id', 'ruta_id');
    }

	public function subRuta()
    {
        return $this->hasManyThrough(SubRuta::class, PedidosShopifiesSubRutaLink::class, 'pedidos_shopify_id', 'id', 'id', 'sub_ruta_id');
    }

	public function upuser_pedidos_link()
	{
		return $this->belongsToMany(UpUser::class, UpUsersPedidosShopifiesLink::class, 'user_id')
		->withPivot('id');
	}

	public function pedido_fecha_link()
	{
		return $this->belongsToMany(PedidoFecha::class, PedidosShopifiesPedidoFechaLink::class, 'pedido_fecha_id')
		->withPivot('id');
	}
	// public function novedades()
    // {
    //     return $this->hasManyThrough(Novedade::class, NovedadesPedidosShopifyLink::class, 'pedidos_shopify_id', 'id', 'id', 'novedad_id');
    // }
}
