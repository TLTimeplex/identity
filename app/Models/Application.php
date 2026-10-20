<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $url
 * @property string $icon
 * @property bool $is_active
 * @property int|null $category_id
 * @property string|null $required_permission
 * @property string $hydra_client_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ApplicationCategory|null $category
 * @property-read Permission|null $requiredPermission
 */
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
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

    /** @return BelongsTo<ApplicationCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ApplicationCategory::class, 'category_id');
    }

    /** @return BelongsTo<Permission, $this> */
    public function requiredPermission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'required_permission');
    }
}
