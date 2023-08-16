<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UpUser
 * 
 * @property int $id
 * @property string|null $username
 * @property string|null $email
 * @property string|null $provider
 * @property string|null $password
 * @property string|null $reset_password_token
 * @property string|null $confirmation_token
 * @property bool|null $confirmed
 * @property bool|null $blocked
 * @property string|null $fecha_alta
 * @property string|null $persona_cargo
 * @property string|null $estado
 * @property string|null $codigo_generado
 * @property string|null $permisos
 * @property string|null $telefono_1
 * @property string|null $telefono_2
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by_id
 * @property int|null $updated_by_id
 * 
 * @property AdminUser|null $admin_user
 * @property Collection|OrdenesRetirosUsersPermissionsUserLink[] $ordenes_retiros_users_permissions_user_links
 * @property Collection|TransportadorasUsersPermissionsUserLink[] $transportadoras_users_permissions_user_links
 * @property Collection|Operadore[] $operadores
 * @property Collection|UpUsersPedidosShopifiesLink[] $up_users_pedidos_shopifies_links
 * @property Collection|UpUsersRoleLink[] $up_users_role_links
 * @property Collection|RolesFront[] $roles_fronts
 * @property Collection|Vendedore[] $vendedores
 *
 * @package App\Models
 */
class UpUser extends Model
{
	protected $table = 'up_users';

	protected $casts = [
		'confirmed' => 'bool',
		'blocked' => 'bool',
		'created_by_id' => 'int',
		'updated_by_id' => 'int'
	];

	protected $hidden = [
		'password',
		'reset_password_token',
		'confirmation_token'
	];

	protected $fillable = [
		'username',
		'email',
		'provider',
		'password',
		'reset_password_token',
		'confirmation_token',
		'confirmed',
		'blocked',
		'fecha_alta',
		'persona_cargo',
		'estado',
		'codigo_generado',
		'permisos',
		'telefono_1',
		'telefono_2',
		'created_by_id',
		'updated_by_id'
	];

	public function admin_user()
	{
		return $this->belongsTo(AdminUser::class, 'updated_by_id');
	}

	public function ordenes_retiros_users_permissions_user_links()
	{
		return $this->hasMany(OrdenesRetirosUsersPermissionsUserLink::class, 'user_id');
	}

	public function transportadoras_users_permissions_user_links()
	{
		return $this->hasMany(TransportadorasUsersPermissionsUserLink::class, 'user_id');
	}

	public function operadores()
	{
		return $this->belongsToMany(Operadore::class, 'up_users_operadore_links', 'user_id')
					->withPivot('id');
	}

	public function up_users_pedidos_shopifies_links()
	{
		return $this->hasMany(UpUsersPedidosShopifiesLink::class, 'user_id');
	}

	public function up_users_role_links()
	{
		return $this->hasMany(UpUsersRoleLink::class, 'user_id');
	}

	public function roles_fronts()
	{
		return $this->belongsToMany(RolesFront::class, 'up_users_roles_front_links', 'user_id')
					->withPivot('id', 'user_order');
	}

	public function vendedores()
	{
		return $this->belongsToMany(Vendedore::class, 'up_users_vendedores_links', 'user_id', 'vendedor_id')
					->withPivot('id', 'vendedor_order', 'user_order');
	}
}
