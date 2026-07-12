@props(['student'])

@php
    // Not enough for the DB field to be set - the file it points to must actually exist on
    // disk, otherwise the <img> tag 404s and the browser shows a broken-image icon instead
    // of falling through to the placeholder avatar below.
    $hasPhoto = $student->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->profile_photo);
    $src = $hasPhoto ? asset('storage/' . $student->profile_photo) : asset('images/logos/avatar.png');
@endphp

<img src="{{ $src }}" alt="{{ $student->full_name }}"
    {{ $attributes->merge(['class' => 'object-cover']) }}>
