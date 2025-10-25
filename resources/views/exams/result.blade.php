@extends('layouts.app')
@section('title', 'Prüfungsergebnis: ' . $attempt->exam->title)

@section('content')
{{-- AdminLTE Content Header --}}
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-poll-h mr-2"></i>Ergebnis: {{ $attempt->exam->title }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') ?? route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.attempts.index') }}">Prüfungsversuche</a></li>
                    <li class="breadcrumb-item active">Ergebnis / Bewertung</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Main Content --}}
<div class="content">
    <div class="container-fluid">
        <div class="row">
            {{-- Linke Spalte: Zusammenfassung & Aktionen --}}
            <div class="col-lg-4">
                {{-- Info Boxen für schnellen Überblick --}}
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-user"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Prüfling</span>
                        <span class="info-box-number">{{ $attempt->user->name ?? 'N/A' }}</span>
                    </div>
                </div>

                @php
                    // Bestanden-Status basierend auf finalem Score oder Auto-Score, falls nicht bewertet
                    $finalScore = $attempt->score;
                    $passMark = optional($attempt->exam)->pass_mark ?? 0; // Verwende optional() für Sicherheit
                    $isPassed = $finalScore !== null && $finalScore >= $passMark;
                    $statusColor = $finalScore !== null ? ($isPassed ? 'bg-success' : 'bg-danger') : 'bg-secondary';
                    $statusIcon = $finalScore !== null ? ($isPassed ? 'fa-check-circle' : 'fa-times-circle') : 'fa-question-circle';
                    $statusText = $finalScore !== null ? ($isPassed ? 'Bestanden' : 'Nicht Bestanden') : 'Ausstehend';
                @endphp

                <div class="info-box mb-3">
                    <span class="info-box-icon {{ $statusColor }} elevation-1"><i class="fas {{ $statusIcon }}"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ergebnis ({{ $passMark }}% benötigt)</span>
                        <span class="info-box-number">
                            {{ $finalScore ?? 'N/A' }}% - {{ $statusText }}
                        </span>
                    </div>
                </div>

                {{-- Karte für Aktionen / Finale Bewertung --}}
                @can('setEvaluated', $attempt)
                <div class="card card-warning card-outline sticky-top">
                     <div class="card-header">
                         <h3 class="card-title"><i class="fas fa-gavel mr-1"></i>Bewertung</h3>
                         <div class="card-tools">
                             <span class="badge {{ $attempt->status === 'evaluated' ? 'badge-success' : ($attempt->status === 'submitted' ? 'badge-warning' : 'badge-info') }}">{{ ucfirst($attempt->status) }}</span>
                         </div>
                     </div>

                    @if ($attempt->status === 'evaluated')
                        <div class="card-body text-center">
                            <p><i class="fas fa-check-circle text-success mr-1"></i>Diese Prüfung wurde bereits final bewertet.</p>
                            <a href="{{ route('admin.exams.attempts.index') }}" class="btn btn-sm btn-default mt-2"><i class="fas fa-arrow-left"></i> Zur Übersicht</a>
                        </div>
                    @else
                        {{-- Finalisierungsformular --}}
                        <form action="{{ route('admin.exams.attempts.update', $attempt) }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <p class="text-info"><i class="fas fa-info-circle mr-1"></i>Überprüfen Sie die Antworten (insbesondere Textfelder) und geben Sie den finalen Score ein.</p>
                                <div class="form-group">
                                    <label for="final_score">Finaler Gesamt-Score (%)</label>
                                    <input type="number" name="final_score" id="final_score" class="form-control @error('final_score') is-invalid @enderror" min="0" max="100" value="{{ old('final_score', round($attempt->score ?? 0)) }}" required>
                                     <small class="form-text text-muted">Auto-Score (ohne Textfelder): <strong>{{ round($attempt->score ?? 0) }}%</strong></small>
                                     @error('final_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                {{-- Select für Modulstatus entfernt --}}
                                <p class="text-danger small mt-3"><i class="fas fa-exclamation-triangle mr-1"></i>Das Speichern setzt den Status des Versuchs auf "evaluated" und ist der finale Schritt.</p>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-save mr-1"></i> Finale Bewertung speichern
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
                @endcan

                 {{-- Button "Zurück zur Übersicht" immer anzeigen --}}
                 @if($attempt->status === 'evaluated')
                    {{-- Oben schon vorhanden --}}
                 @else
                    <a href="{{ route('admin.exams.attempts.index') }}" class="btn btn-block btn-default mt-3"><i class="fas fa-arrow-left"></i> Zur Übersicht (ohne Speichern)</a>
                 @endif
            </div>

            {{-- Rechte Spalte: Detailauswertung --}}
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-tasks mr-1"></i>Detailauswertung der Antworten</h3>
                        @if ($finalScore !== null)
                        <div class="card-tools">
                            <div class="progress progress-sm" style="width: 100px;">
                                <div class="progress-bar {{ $statusColor }}" role="progressbar" aria-valuenow="{{$finalScore}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$finalScore}}%"></div>
                            </div>
                             <span class="ml-1">{{ $finalScore }}%</span>
                        </div>
                        @endif
                    </div>
                    <div class="card-body">
                        @if(optional($attempt->exam)->questions->count() > 0) {{-- Sicherer Zugriff --}}
                            @foreach($attempt->exam->questions as $index => $question)
                                @php
                                    $userAnswersForQuestion = $attempt->answers->where('question_id', $question->id);
                                    $isCorrect = false;
                                    // Verwende Standard AdminLTE alert Klassen
                                    $answerFeedbackClass = 'alert-secondary'; // Standard für Text/Unbekannt
                                    $answerIcon = 'fa-pencil-alt';

                                    if ($question->type === 'single_choice') {
                                        $correctOption = optional($question->options)->where('is_correct', true)->first();
                                        $userAnswer = $userAnswersForQuestion->first();
                                        $isCorrect = $correctOption && $userAnswer && $correctOption->id == $userAnswer->option_id;
                                        $answerFeedbackClass = $isCorrect ? 'alert-success' : 'alert-danger';
                                        $answerIcon = $isCorrect ? 'fa-check' : 'fa-times';
                                    } elseif ($question->type === 'multiple_choice') {
                                        $correctOptionIds = optional($question->options)->where('is_correct', true)->pluck('id')->sort()->values() ?? collect();
                                        $userOptionIds = $userAnswersForQuestion->pluck('option_id')->sort()->values();
                                        $isCorrect = $correctOptionIds->isNotEmpty() && $correctOptionIds->all() == $userOptionIds->all(); // Prüfe auch auf leere korrekte IDs
                                        $answerFeedbackClass = $isCorrect ? 'alert-success' : 'alert-danger';
                                        $answerIcon = $isCorrect ? 'fa-check' : 'fa-times';
                                    } elseif ($question->type === 'text_field') {
                                         $answerFeedbackClass = 'alert-light'; // Hellgrau für Text
                                         $answerIcon = 'fa-align-left';
                                    }
                                @endphp

                                {{-- Verwende Standard Callout ohne benutzerdefinierte Klassen --}}
                                <div class="callout callout-{{ str_replace('alert-', '', $answerFeedbackClass) }} mb-4">
                                    {{-- Verwende alert Klassen für den inneren Hintergrund --}}
                                    <div class="alert {{ $answerFeedbackClass }} p-3 mb-0">
                                        <h5 class="mb-3">
                                            <i class="fas {{ $answerIcon }} mr-2"></i>
                                            <strong>Frage {{ $index + 1 }}:</strong> {{ $question->question_text }}
                                            <small class="float-right badge badge-secondary">{{ Str::ucfirst(str_replace('_', '-', $question->type)) }}</small>
                                        </h5>

                                        @switch($question->type)
                                            @case('single_choice')
                                                @php $userOptionId = $userAnswersForQuestion->first()->option_id ?? null; @endphp
                                                <ul class="list-unstyled ml-4">
                                                    @forelse(optional($question->options) as $option)
                                                        <li>
                                                            @if($option->is_correct)
                                                                <i class="fas fa-check-circle text-success mr-1"></i>
                                                            @elseif($userOptionId == $option->id && !$option->is_correct)
                                                                <i class="fas fa-times-circle text-danger mr-1"></i>
                                                            @else
                                                                <i class="far fa-circle text-muted mr-1"></i>
                                                            @endif
                                                            <span class="{{ $userOptionId == $option->id ? 'font-weight-bold' : '' }}">{{ $option->option_text }}</span>
                                                        </li>
                                                    @empty
                                                        <li class="text-danger">Keine Optionen definiert!</li>
                                                    @endforelse
                                                </ul>
                                                @break

                                            @case('multiple_choice')
                                                @php $userOptionIds = $userAnswersForQuestion->pluck('option_id'); @endphp
                                                <ul class="list-unstyled ml-4">
                                                     @forelse(optional($question->options) as $option)
                                                        <li>
                                                            @if($option->is_correct && $userOptionIds->contains($option->id))
                                                                <i class="fas fa-check-square text-success mr-1"></i>
                                                            @elseif(!$option->is_correct && $userOptionIds->contains($option->id))
                                                                <i class="fas fa-times-circle text-danger mr-1"></i>
                                                            @elseif($option->is_correct && !$userOptionIds->contains($option->id))
                                                                <i class="far fa-square text-muted mr-1"></i>
                                                            @else
                                                                <i class="far fa-square text-muted mr-1"></i>
                                                            @endif
                                                            <span>{{ $option->option_text }}</span>
                                                        </li>
                                                     @empty
                                                          <li class="text-danger">Keine Optionen definiert!</li>
                                                     @endforelse
                                                </ul>
                                                @break

                                            @case('text_field')
                                                <p class="ml-4 mb-1"><strong>Antwort des Prüflings:</strong></p>
                                                {{-- Einfaches Blockquote oder p-Tag für Text --}}
                                                <p class="ml-4 font-italic bg-white p-2 border rounded">
                                                    {{ $userAnswersForQuestion->first()->text_answer ?? 'Keine Antwort gegeben.' }}
                                                </p>
                                                <small class="ml-4 text-muted">Hinweis: Manuell in finalen Score einrechnen.</small>
                                                @break

                                            @default
                                                 <p class="text-danger">Unbekannter Fragetyp.</p>
                                        @endswitch
                                    </div>
                                </div> {{-- /.callout --}}
                            @endforeach
                        @else
                             <p class="text-center text-muted">Für diese Prüfung wurden keine Fragen gefunden.</p>
                        @endif
                    </div> {{-- /.card-body --}}
                </div> {{-- /.card --}}
            </div> {{-- /.col-lg-8 --}}
        </div> {{-- /.row --}}
    </div> {{-- /.container-fluid --}}
</div> {{-- /.content --}}
@endsection
