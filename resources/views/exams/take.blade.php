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
                    <div class="alert alert-danger">
                        <h5 class="alert-heading"><i class="icon fas fa-exclamation-triangle"></i> Wichtiger Hinweis</h5>
                        Das Verlassen dieser Seite (Tab-Wechsel, Minimieren des Fensters) während der Prüfung wird protokolliert und kann als Betrugsversuch gewertet werden. Die Prüfung muss in einer Sitzung abgeschlossen werden.
                    </div>

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
                                            @foreach($question->options as $option)
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="option_{{ $option->id }}" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="custom-control-input" required>
                                                    <label class="custom-control-label" for="option_{{ $option->id }}">{{ $option->option_text }}</label>
                                                </div>
                                            @endforeach
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Anti-Cheat: Überwacht, ob der Tab verlassen wird
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                // sendBeacon ist zuverlässiger als fetch/axios, um Daten zu senden, bevor eine Seite geschlossen wird.
                const flagUrl = '{{ route("exams.flag", $attempt) }}';
                navigator.sendBeacon(flagUrl, new Blob([JSON.stringify({_token: '{{ csrf_token() }}'})], {type : 'application/json'}));
            }
        });

        // Verhindert versehentliches Schließen oder Neuladen der Seite
        window.addEventListener('beforeunload', function (e) {
            e.preventDefault();
            e.returnValue = 'Sind Sie sicher, dass Sie die Prüfung verlassen möchten? Ihr Fortschritt geht verloren.';
        });

        // Deaktiviert den "Zurück"-Button im Browser, während die Prüfung läuft
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };

        // Entfernt die Schutzmechanismen beim Absenden des Formulars
        document.getElementById('exam-form').addEventListener('submit', function() {
            window.onbeforeunload = null;
            window.onpopstate = null;
        });
    });
</script>
@endpush

