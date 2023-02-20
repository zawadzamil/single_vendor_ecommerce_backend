<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Category extends Model
{
    use HasFactory, SoftDeletes;

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
        'slug',
        'description',
        'parent_id',
        'created_by'
    ];

    // Created By
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function tree(){
        $allCategories = Category::all();
         $rootCategories = $allCategories->whereNull('parent_id')->values();
         self::formatTree($rootCategories,$allCategories);
         return $rootCategories;
    }

    public static function formatTree($categories,$allCategories){
        foreach ($categories as $category){
            $category->children = $allCategories->where('parent_id', $category->id)->values();

            if($category->children->isNotEmpty()){
                self::formatTree($category->children, $allCategories);
            }
        }
    }
}
