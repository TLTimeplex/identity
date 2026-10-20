<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationCategory extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicationCategoryFactory> */
    use HasFactory;

    protected $table = 'application_category';

    protected $fillable = [
        'name',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class, 'category_id');
    }
}
