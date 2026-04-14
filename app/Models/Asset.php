<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'brand',
        'model',
        'serial_number',
        'category',
        'condition',
        'status',
        'barcode',
        'qr_code_hash',
        'specifications',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specifications' => 'array',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
