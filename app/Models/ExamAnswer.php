<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    use HasFactory;
    protected $fillable = ['exam_attempt_id', 'question_id', 'option_id', 'text_answer', 'is_correct_at_time_of_answer'];
    protected $casts = ['is_correct_at_time_of_answer' => 'boolean'];

    public function attempt() { return $this->belongsTo(ExamAttempt::class); }
    public function question() { return $this->belongsTo(Question::class); }
    public function option() { return $this->belongsTo(Option::class); }
}
