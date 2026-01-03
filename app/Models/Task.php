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

    public static function getEffortSql(): string
    {
        $cases = [];
        foreach (TaskDifficulty::cases() as $difficulty) {
            $cases[] = sprintf('WHEN %d THEN %d', $difficulty->value, $difficulty->points());
        }

        return sprintf(
            'CASE difficulty %s ELSE 0 END',
            implode(' ', $cases)
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
