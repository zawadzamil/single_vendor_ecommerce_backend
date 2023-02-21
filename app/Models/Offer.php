<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Offer extends Model
{
    use HasFactory,SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }

    protected $fillable = [
        'name',
        'description',
        'discount',
        'start_date',
        'end_date',
       'created_by',
        'status'
    ];

    public function products(){
        return $this->hasMany(Product::class);
    }
}
