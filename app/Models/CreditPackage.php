<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A credit pack on sale. Price is in whole rupiah (IDR has no minor unit).
 *
 * @property int $id
 * @property string $name
 * @property int $credits
 * @property int $price
 * @property bool $is_active
 * @property int $sort_order
 */
#[Fillable(['name', 'credits', 'price', 'is_active', 'sort_order'])]
class CreditPackage extends Model
{
    /**
     * @param  Builder<CreditPackage>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
