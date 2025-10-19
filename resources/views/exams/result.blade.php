@extends('layouts.app')
@section('title', 'Prüfungsergebnis')

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                     <h1 class="m-0">Ergebnis für: {{ $attempt->exam->title }}</h1>
                </div>
                 <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Prüfungsergebnis</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                {{-- Zusammenfassungs-Karte --}}
                <div class="col-lg-10 offset-lg-1">
                    @php
                        $passed = $attempt->score >= $attempt->exam->pass_mark;
                    @endphp
                    <div class="card {{ $passed ? 'card-success' : 'card-danger' }} card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas {{ $passed ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                Prüfung {{ $passed ? 'bestanden' : 'nicht bestanden' }}
                            </h3>
                        </div>
                        <div class="card-body text-center">
                            <div class="display-4">{{ round($attempt->score) }}%</div>
                            <p class="lead">Erreichte Punktzahl</p>
                            
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar {{ $passed ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ $attempt->score }}%;" aria-valuenow="{{ $attempt->score }}" aria-valuemin="0" aria-valuemax="100">{{ round($attempt->score) }}%</div>
                            </div>
                            
                            <p class="mt-3">Mindestpunktzahl zum Bestehen: <strong>{{ $attempt->exam->pass_mark }}%</strong></p>
                            <hr>
                            <p>Prüfung abgeschlossen am: {{ $attempt->completed_at->format('d.m.Y \u\m H:i') }} Uhr</p>
                            
                            @if($attempt->focus_lost_count > 0)
                                <div class="alert alert-warning mt-3">
                                    <strong><i class="fas fa-exclamation-triangle"></i> Achtung:</strong> Während dieser Prüfung wurde {{ $attempt->focus_lost_count }} Mal der Fokus verloren. Dies wurde für den Prüfer protokolliert.
                                </div>
                            @endif
                            
                            <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-tachometer-alt"></i> Zurück zum Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                {{-- KORRIGIERT: Detailauswertung der Fragen --}}
                <div class="col-lg-10 offset-lg-1 mt-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Detailauswertung</h3>
                        </div>
                        <div class="card-body">
                            @foreach($attempt->exam->questions as $index => $question)
                                @php
                                    // Logik zur Bestimmung, ob die Antwort korrekt war
                                    $userAnswersForQuestion = $attempt->answers->where('question_id', $question->id);
                                    $isCorrect = false;

                                    if ($question->type === 'single_choice') {
                                        $correctOptionId = $question->options->where('is_correct', true)->first()->id ?? null;
                                        $userOptionId = $userAnswersForQuestion->first()->option_id ?? null;
                                        $isCorrect = ($correctOptionId === $userOptionId);
                                    } elseif ($question->type === 'multiple_choice') {
                                        $correctOptionIds = $question->options->where('is_correct', true)->pluck('id')->sort()->values();
                                        $userOptionIds = $userAnswersForQuestion->pluck('option_id')->sort()->values();
                                        $isCorrect = $correctOptionIds->all() === $userOptionIds->all();
                                    }
                                    // Textfelder werden nicht automatisch bewertet
                                @endphp

                                <div class="p-3 mb-3 rounded {{ $question->type !== 'text_field' ? ($isCorrect ? 'alert-success' : 'alert-danger') : 'alert-secondary' }}">
                                    <p class="font-weight-bold">Frage {{ $index + 1 }}: {{ $question->question_text }}</p>
                                    
                                    @switch($question->type)
                                        @case('single_choice')
                                            @php $userOptionId = $userAnswersForQuestion->first()->option_id ?? null; @endphp
                                            <ul class="list-unstyled">
                                                @foreach($question->options as $option)
                                                    <li>
                                                        @if($option->is_correct)
                                                            <i class="fas fa-check-circle text-success"></i> {{-- Korrekte Antwort --}}
                                                        @elseif($userOptionId === $option->id && !$option->is_correct)
                                                            <i class="fas fa-times-circle text-danger"></i> {{-- Falsch gewählte Antwort --}}
                                                        @else
                                                            <i class="far fa-circle text-muted"></i> {{-- Neutrale Option --}}
                                                        @endif
                                                        <span class="{{ $userOptionId === $option->id ? 'font-weight-bold' : '' }}">{{ $option->option_text }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            @break

                                        @case('multiple_choice')
                                            @php $userOptionIds = $userAnswersForQuestion->pluck('option_id'); @endphp
                                            <ul class="list-unstyled">
                                                @foreach($question->options as $option)
                                                    <li>
                                                        @if($option->is_correct && $userOptionIds->contains($option->id))
                                                            <i class="fas fa-check-square text-success"></i> {{-- Richtig ausgewählt --}}
                                                        @elseif(!$option->is_correct && $userOptionIds->contains($option->id))
                                                            <i class="fas fa-times-circle text-danger"></i> {{-- Falsch ausgewählt --}}
                                                        @elseif($option->is_correct && !$userOptionIds->contains($option->id))
                                                            <i class="far fa-square text-muted"></i> {{-- Richtig, aber nicht ausgewählt --}}
                                                        @else
                                                            <i class="far fa-square text-muted"></i> {{-- Falsch und nicht ausgewählt --}}
                                                        @endif
                                                        <span>{{ $option->option_text }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            @break

                                        @case('text_field')
                                            <p><strong>Ihre Antwort:</strong></p>
                                            <blockquote class="blockquote">
                                                <p class="mb-0"><em>{{ $userAnswersForQuestion->first()->text_answer ?? 'Keine Antwort gegeben.' }}</em></p>
                                            </blockquote>
                                            <small class="text-muted">Textantworten werden manuell bewertet und fließen nicht in die automatische Punktzahl ein.</small>
                                            @break
                                    @endswitch
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
