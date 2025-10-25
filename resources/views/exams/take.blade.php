@extends('layouts.app')
{{-- Titel angepasst, da Modulbezug fehlt --}}
@section('title', 'Prüfung ablegen: ' . $attempt->exam->title)

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-file-signature nav-icon"></i> Prüfung: {{ $attempt->exam->title }}</h1>
                    {{-- ZEILE ENTFERNT: <p class="text-muted mb-0">Modul: {{ $attempt->exam->trainingModule->name }}</p> --}}
                    {{-- Optional: Beschreibung der Prüfung anzeigen --}}
                    @if($attempt->exam->description)
                        <p class="text-muted mb-0">{{ $attempt->exam->description }}</p>
                    @endif
                </div>
                {{-- Breadcrumb kann bleiben oder angepasst werden --}}
                {{-- <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Prüfung</li>
                    </ol>
                </div> --}}
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
                                <h3 class="card-title">Fragebogen ({{ $attempt->exam->questions->count() }} Fragen)</h3>
                                {{-- Optional: Timer hinzufügen? --}}
                            </div>
                            <div class="card-body">
                                @if($attempt->exam->questions->isEmpty())
                                     <p class="text-center text-warning">Diese Prüfung enthält noch keine Fragen.</p>
                                @else
                                    @foreach($attempt->exam->questions as $index => $question)
                                        <div class="question-block mb-4 p-3 border rounded">
                                            <h5>Frage {{ $index + 1 }}: {{ $question->question_text }}</h5>
                                            <div class="form-group mt-3">

                                                @switch($question->type)

                                                    @case('single_choice')
                                                        @forelse($question->options as $option)
                                                            <div class="custom-control custom-radio">
                                                                <input type="radio" id="option_{{ $option->id }}" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="custom-control-input" required>
                                                                <label class="custom-control-label" for="option_{{ $option->id }}">{{ $option->option_text }}</label>
                                                            </div>
                                                        @empty
                                                             <p class="text-danger">Keine Antwortoptionen für diese Frage vorhanden.</p>
                                                        @endforelse
                                                        @break

                                                    @case('multiple_choice')
                                                         {{-- Hinweis hinzufügen, dass mindestens eine Antwort erforderlich ist, falls gewünscht --}}
                                                         <small class="form-text text-muted mb-2">Mehrere Antworten können korrekt sein.</small>
                                                        @forelse($question->options as $option)
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" id="option_{{ $option->id }}" name="answers[{{ $question->id }}][]" value="{{ $option->id }}" class="custom-control-input">
                                                                <label class="custom-control-label" for="option_{{ $option->id }}">{{ $option->option_text }}</label>
                                                            </div>
                                                         @empty
                                                              <p class="text-danger">Keine Antwortoptionen für diese Frage vorhanden.</p>
                                                         @endforelse
                                                        @break

                                                    @case('text_field')
                                                        <textarea name="answers[{{ $question->id }}]" class="form-control" rows="3" placeholder="Ihre Antwort..." required></textarea>
                                                        @break

                                                    @default
                                                         <p class="text-danger">Unbekannter Fragetyp.</p>
                                                @endswitch

                                            </div>
                                        </div>
                                        @if(!$loop->last) <hr class="my-4"> @endif
                                    @endforeach
                                @endif
                            </div>
                            <div class="card-footer">
                                {{-- Button nur aktivieren, wenn Fragen vorhanden sind --}}
                                <button type="submit" class="btn btn-success float-right" @if($attempt->exam->questions->isEmpty()) disabled @endif
                                    {{-- Zusätzliche Bestätigung beim Absenden? --}}
                                    onclick="return confirm('Möchten Sie die Prüfung wirklich abschließen und einreichen? Sie können danach keine Änderungen mehr vornehmen.');">
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
