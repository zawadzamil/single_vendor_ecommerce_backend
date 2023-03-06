<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'brand_id',
        'offer_id',
        'gender',
        'slug',
        'created_by'
    ];
    protected $casts = [
        'gender' => GenderEnum::class
    ];
    protected $hidden = ['created_by', 'created_at', 'updated_at', 'deleted_at','offer'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function offer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
    public  function variant(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductVariation::class);
    }

    // Stock Management
    public function stock(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductStock::class);
    }
    public function reserveStock($quantity)
    {
        return $this->stock->reserve($quantity);
    }

    public function unreserveStock($quantity)
    {
        return $this->stock->unreserve($quantity);
    }

    public function fulfillStock($quantity)
    {
        return $this->stock->fulfill($quantity);
    }

    public function availableStock()
    {
        return $this->stock->available_quantity();
    }

    // Image
    public function image(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Image::class);
    }
}
