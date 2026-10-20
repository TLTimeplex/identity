<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ApplicationCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'category_id');
    }
}
