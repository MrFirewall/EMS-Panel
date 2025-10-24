@extends('layouts.app')
@section('title', 'Prüfungsergebnis (Ausbilderansicht)')

@section('content')
{{-- AdminLTE Content Header --}}

<div class="content-header">
<div class="container-fluid">
<div class="row mb-2">
<div class="col-sm-6">
<h1 class="m-0">Ergebnis für: {{ $attempt->exam->title }}</h1>
<p class="text-muted mb-0">Prüfling: {{ $attempt->user->name ?? 'N/A' }}</p>
</div>
<div class="col-sm-6">
<ol class="breadcrumb float-sm-right">
<li class="breadcrumb-item"><a href="{{ route('admin.exams.attempts.index') }}">Versuche</a></li>
<li class="breadcrumb-item active">Prüfungsbewertung</li>
</ol>
</div>
</div>
</div>
</div>

{{-- Main Content --}}

<div class="content">
<div class="container-fluid">
<div class="row">

        {{-- Admin Bewertungs- und Finalisierungsformular --}}
        @can('setEvaluated', $attempt)
            <div class="col-lg-10 offset-lg-1 mb-4">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-hammer"></i> Manuelle Bewertung & Finalisierung</h3>
                        <small class="float-right text-muted">Status: {{ ucfirst($attempt->status) }}</small>
                    </div>
                    
                    @if ($attempt->status === 'evaluated')
                        <div class="card-body">
                            <div class="alert alert-success">
                                Diese Prüfung wurde bereits final bewertet. Ergebnis: <strong>{{ $attempt->score }}%</strong> ({{ $attempt->score >= $attempt->exam->pass_mark ? 'Bestanden' : 'Nicht bestanden' }}).
                            </div>
                            <a href="{{ route('admin.exams.attempts.index') }}" class="btn btn-default"><i class="fas fa-arrow-left"></i> Zurück zur Übersicht</a>
                        </div>
                    @else
                        <form action="{{ route('admin.exams.attempts.update', $attempt) }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <p class="lead text-danger">Achtung: Dies ist der finale Schritt und setzt den Modulstatus des Prüflings!</p>
                                
                                <div class="form-group">
                                    <label for="final_score">Manueller Gesamt-Score (einschließlich Textfelder)</label>
                                    <input type="number" name="final_score" class="form-control" min="0" max="100" value="{{ round($attempt->score ?? 0) }}" required>
                                    <small class="form-text text-muted">Die automatisch berechnete Punktzahl ohne Textfelder beträgt: **{{ round($attempt->score ?? 0) }}%**</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status_result">Modulstatus setzen:</label>
                                    <select name="status_result" class="form-control" required>
                                        <option value="bestanden">Bestanden (Setzt Modulstatus auf "bestanden")</option>
                                        <option value="nicht_bestanden">Nicht bestanden (Setzt Modulstatus auf "nicht_bestanden")</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success float-right">
                                    <i class="fas fa-save"></i> Finale Bewertung speichern & Status setzen
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endcan

        {{-- Detaillierte Auswertung (bleibt wie zuvor) --}}
        <div class="col-lg-10 offset-lg-1">
            @php
                $passed = $attempt->score >= $attempt->exam->pass_mark;
            @endphp
            <div class="card {{ $passed ? 'card-success' : 'card-danger' }} card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas {{ $passed ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                        Automatische Prüfung {{ $passed ? 'bestanden' : 'nicht bestanden' }} ({{ round($attempt->score) }}%)
                    </h3>
                </div>
                <div class="card-body text-center">
                    <p class="lead">Mindestpunktzahl zum Bestehen: <strong>{{ $attempt->exam->pass_mark }}%</strong></p>
                    <hr>
                    <a href="{{ route('admin.exams.attempts.index') }}" class="btn btn-default mt-3">
                        <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
                    </a>
                </div>
            </div>
        </div>

        {{-- Detailauswertung der Fragen --}}
        <div class="col-lg-10 offset-lg-1 mt-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detailauswertung der Antworten</h3>
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
                                $isCorrect = ($correctOptionId == $userOptionId);
                            } elseif ($question->type === 'multiple_choice') {
                                $correctOptionIds = $question->options->where('is_correct', true)->pluck('id')->sort()->values();
                                $userOptionIds = $userAnswersForQuestion->pluck('option_id')->sort()->values();
                                $isCorrect = $correctOptionIds->all() == $userOptionIds->all();
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
                                                @elseif($userOptionId == $option->id && !$option->is_correct)
                                                    <i class="fas fa-times-circle text-danger"></i> {{-- Falsch gewählte Antwort --}}
                                                @else
                                                    <i class="far fa-circle text-muted"></i> {{-- Neutrale Option --}}
                                                @endif
                                                <span class="{{ $userOptionId == $option->id ? 'font-weight-bold' : '' }}">{{ $option->option_text }}</span>
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
                                    <p><strong>Antwort des Prüflings:</strong></p>
                                    <blockquote class="blockquote border-left-secondary pl-3">
                                        <p class="mb-0"><em>{{ $userAnswersForQuestion->first()->text_answer ?? 'Keine Antwort gegeben.' }}</em></p>
                                    </blockquote>
                                    <small class="text-muted">Hinweis: Diese Antwort muss manuell bewertet werden.</small>
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
