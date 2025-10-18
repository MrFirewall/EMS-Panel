<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    protected $fillable = ['training_module_id', 'title', 'description', 'pass_mark'];

    public function trainingModule() { return $this->belongsTo(TrainingModule::class); }
    public function questions() { return $this->hasMany(Question::class); }
    public function attempts() { return $this->hasMany(ExamAttempt::class); }
}