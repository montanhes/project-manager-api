<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function scopeWithProgress($query): void
    {
        $effortSql = Task::getEffortSql();

        $progressSubquery = Task::selectRaw("
            COALESCE(
                ROUND(
                    SUM(
                        CASE WHEN completed = 1 THEN
                            ($effortSql)
                        ELSE 0
                        END
                    ) /
                    NULLIF(
                        SUM(
                            $effortSql
                        ),
                        0
                    ) * 100,
                    2
                ),
                0.0
            )
        ")
        ->whereColumn('project_id', 'projects.id');

        $query->addSelect(['progress' => $progressSubquery]);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
