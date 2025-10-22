<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExamAttempt extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'uuid',
        'exam_id', 'user_id', 'started_at', 'completed_at', 'score', 'status', 'flags'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'flags' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function exam() { return $this->belongsTo(Exam::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function answers() { return $this->hasMany(ExamAnswer::class); }
    /**
     * Holt den Routen-Schlüssel für das Model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid'; // Sagt Laravel, dass es immer die 'uuid'-Spalte für Routen verwenden soll
    }
}