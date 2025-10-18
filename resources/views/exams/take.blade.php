@extends('layouts.app')
@section('title', 'Prüfung: ' . $attempt->exam->title)

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-file-signature nav-icon"></i> Prüfung: {{ $attempt->exam->title }}</h1>
                    <p class="text-muted mb-0">Modul: {{ $attempt->exam->trainingModule->name }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
             <div class="row">
                <div class="col-12">
                    <form action="{{ route('exams.submit', $attempt) }}" method="POST" id="exam-form">
                        @csrf
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Fragebogen</h3>
                            </div>
                            <div class="card-body">
                                @foreach($attempt->exam->questions as $index => $question)
                                    <div class="question-block mb-4 p-3 border rounded">
                                        <h5>Frage {{ $index + 1 }}: {{ $question->question_text }}</h5>
                                        <div class="form-group mt-3">
                                            
                                            {{-- KORRIGIERT: Switch-Anweisung für verschiedene Fragetypen --}}
                                            @switch($question->type)
                                                
                                                @case('single_choice')
                                                    @foreach($question->options as $option)
                                                        <div class="custom-control custom-radio">
                                                            {{-- Name ist 'answers[question_id]' --}}
                                                            <input type="radio" id="option_{{ $option->id }}" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="custom-control-input" required>
                                                            <label class="custom-control-label" for="option_{{ $option->id }}">{{ $option->option_text }}</label>
                                                        </div>
                                                    @endforeach
                                                    @break

                                                @case('multiple_choice')
                                                    @foreach($question->options as $option)
                                                        <div class="custom-control custom-checkbox">
                                                            {{-- Name ist 'answers[question_id][]' um ein Array zu empfangen --}}
                                                            <input type="checkbox" id="option_{{ $option->id }}" name="answers[{{ $question->id }}][]" value="{{ $option->id }}" class="custom-control-input">
                                                            <label class="custom-control-label" for="option_{{ $option->id }}">{{ $option->option_text }}</label>
                                                        </div>
                                                    @endforeach
                                                    @break

                                                @case('text_field')
                                                    <textarea name="answers[{{ $question->id }}]" class="form-control" rows="3" placeholder="Ihre Antwort..." required></textarea>
                                                    @break
                                                    
                                            @endswitch

                                        </div>
                                    </div>
                                    @if(!$loop->last) <hr class="my-4"> @endif
                                @endforeach
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success float-right">
                                    <i class="fas fa-check-circle"></i> Prüfung abschließen und einreichen
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection