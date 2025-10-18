@extends('layouts.app')
@section('title', 'Details: ' . $exam->title)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-info-circle nav-icon"></i> Details: {{ $exam->title }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}">Prüfungen</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4">
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Stammdaten</h3></div>
                    <div class="card-body">
                        <strong><i class="fas fa-book mr-1"></i> Modul</strong>
                        <p class="text-muted">{{ $exam->trainingModule->name }}</p><hr>
                        <strong><i class="fas fa-check-circle mr-1"></i> Bestehensgrenze</strong>
                        <p class="text-muted">{{ $exam->pass_mark }}%</p><hr>
                        <strong><i class="far fa-file-alt mr-1"></i> Beschreibung</strong>
                        <p class="text-muted">{{ $exam->description ?? 'Keine.' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Fragenkatalog</h3></div>
                    <div class="card-body">
                        @foreach($exam->questions as $question)
                        <div class="mb-4">
                            <h5><strong>Frage {{ $loop->iteration }}:</strong> {{ $question->question_text }}</h5>
                            <ul class="list-group">
                                @foreach($question->options as $option)
                                <li class="list-group-item {{ $option->is_correct ? 'list-group-item-success' : '' }}">
                                    @if($option->is_correct)
                                        <i class="fas fa-check text-success mr-2"></i>
                                    @else
                                        <i class="fas fa-times text-danger mr-2"></i>
                                    @endif
                                    {{ $option->option_text }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
