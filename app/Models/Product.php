<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 * 
 * @property int $product_id
 * @property string|null $product_name
 * @property int|null $stock
 * @property string|null $features
 * @property float|null $price
 * @property string|null $url_img
 * @property int|null $warehouse_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Warehouse|null $warehouse
 *
 * @package App\Models
 */
class Product extends Model
{
	protected $table = 'products';
	protected $primaryKey = 'product_id';

	protected $casts = [
		'stock' => 'int',
		'price' => 'float',
		'warehouse_id' => 'int'
	];

	protected $fillable = [
		'product_name',
		'stock',
		'features',
		'price',
		'url_img',
		'warehouse_id'
	];

	public function warehouse()
	{
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');

	}
	
}
