@extends('layouts.app') @section('content')
<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Bürgerakte: {{ $citizen->name }}</h1>
        <a href="{{ route('citizens.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Zurück zur Übersicht
        </a>
    </div>

    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-gray-700 font-bold">Name:</p>
                <p>{{ $citizen->name }}</p>
            </div>
            <div>
                <p class="text-gray-700 font-bold">Geburtsdatum:</p>
                <p>{{ $citizen->date_of_birth ? \Carbon\Carbon::parse($citizen->date_of_birth)->format('d.m.Y') : 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-700 font-bold">Telefonnummer:</p>
                <p>{{ $citizen->phone_number ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-700 font-bold">Adresse:</p>
                <p>{{ $citizen->address ?? 'N/A' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-gray-700 font-bold">Notizen:</p>
                <p class="whitespace-pre-wrap">{{ $citizen->notes ?? 'Keine Notizen vorhanden.' }}</p>
            </div>
        </div>
        <div class="mt-6">
            <a href="{{ route('citizens.edit', $citizen) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                Akte bearbeiten
            </a>
        </div>
    </div>

    <h2 class="text-xl font-bold mt-8 mb-4">Bisherige Berichte</h2>
    <div class="bg-white shadow-md rounded">
        <ul class="divide-y divide-gray-200">
            @forelse ($citizen->reports as $report)
                <li class="p-4">
                    <h3 class="font-semibold">{{ $report->title }}</h3> <p class="text-sm text-gray-600">Erstellt am: {{ $report->created_at->format('d.m.Y H:i') }}</p>
                    <div class="mt-2 prose max-w-none">
                        {!! $report->content !!} </div>
                </li>
            @empty
                <li class="p-4 text-gray-500">Für diesen Bürger sind noch keine Berichte vorhanden.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection