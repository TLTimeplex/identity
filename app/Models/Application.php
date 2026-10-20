<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Application extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicationFactory> */
    use HasFactory;

    protected $table = 'application';

    protected $fillable = [
        'name',
        'description',
        'url',
        'icon',
        'is_active',
        'category_id',
        'required_permission',
        'hydra_client_id',
    ];

    public function category()
    {
        return $this->belongsTo(ApplicationCategory::class, 'category_id');
    }

    public function requiredPermission()
    {
        return $this->belongsTo(Permission::class, 'required_permission');
    }
}
