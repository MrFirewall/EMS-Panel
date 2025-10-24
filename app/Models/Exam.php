<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'pass_mark'];

    public function questions() { return $this->hasMany(Question::class); }
    public function attempts() { return $this->hasMany(ExamAttempt::class); }
}
