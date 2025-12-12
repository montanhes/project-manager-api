<?php

namespace App\Models;

use App\Enums\TaskDifficulty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'completed',
        'difficulty',
        'project_id',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'difficulty' => TaskDifficulty::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
