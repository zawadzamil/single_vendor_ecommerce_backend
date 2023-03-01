<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class ProductStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'quantity',
        'reserved_quantity',
        'created_by'
    ];
    protected $hidden = ['created_by', 'created_at', 'updated_at', 'deleted_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }

    // Relation
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reserve($quantity): bool
    {
        if ($this->available_quantity() >= $quantity) {
            $this->reserved_quantity += $quantity;
            $this->save();

            return true;
        }

        return false;
    }

    public function unreserve($quantity): bool
    {
        if ($this->reserved_quantity >= $quantity) {
            $this->reserved_quantity -= $quantity;
            $this->save();

            return true;
        }

        return false;
    }

    public function fulfill($quantity): bool
    {
        if ($this->reserved_quantity >= $quantity) {
            $this->quantity -= $quantity;
            $this->reserved_quantity -= $quantity;
            $this->save();

            return true;
        }

        return false;
    }

    public function available_quantity()
    {
        return $this->quantity - $this->reserved_quantity;
    }


}
