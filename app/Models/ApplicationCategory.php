<?php

namespace App\Models;

use Database\Factories\ApplicationCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApplicationCategory extends Model
{
    /** @use HasFactory<ApplicationCategoryFactory> */
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
