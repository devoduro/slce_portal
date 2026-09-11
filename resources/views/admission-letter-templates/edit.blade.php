@extends('components.app-layout')

@section('title', 'Admission Letter - ' . $academicYear->name)
@section('subtitle', 'Edit, preview, and save the admission letter text for this academic year')

@section('content')
<div class="py-4 space-y-6 max-w-4xl">

    <div class="p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 text-sm rounded">
        <p class="font-medium mb-1">Placeholders</p>
        <p>Use these anywhere in the text below - they're replaced with the real applicant's details when the letter is generated:</p>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach($placeholders as $token => $label)
                <code class="px-2 py-1 bg-white border border-blue-200 rounded text-xs" title="{{ $label }}">{{ $token }}</code>
            @endforeach
        </div>
        <p class="mt-2 text-xs">Basic HTML tags (e.g. &lt;strong&gt;, &lt;br&gt;) are allowed. The "Dear {name}," greeting and signature block are added automatically - write only the body paragraphs here.</p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6">
        <label for="preview_admission" class="block text-sm font-medium text-gray-700 mb-1">Preview against</label>
        <select id="preview_admission" class="w-full md:w-96 rounded-md border-gray-300 text-sm">
            <option value="">Sample applicant (no real data)</option>
            @foreach($admissions as $a)
                <option value="{{ $a->id }}">{{ $a->full_name }} ({{ $a->applicant_number }})</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">
            @if($admissions->isEmpty())
                No admissions exist yet for {{ $academicYear->name }} - previews will use sample data until one is imported.
            @else
                Pick a real applicant from {{ $academicYear->name }} to preview their actual name, programme, and passport photo on the letter, or leave as sample data.
            @endif
        </p>
    </div>

    <form action="{{ route('admission-letter-templates.update', $academicYear) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold text-gray-800">Provisional Letter</h3>
                <button type="button" onclick="previewLetter('provisional')" class="px-3 py-1.5 bg-gray-100 rounded-md text-xs hover:bg-gray-200">
                    <i class="fas fa-eye mr-1"></i> Preview
                </button>
            </div>
            <p class="text-xs text-gray-500 mb-2">Shown before the admission's payment has been confirmed.</p>
            <textarea id="provisional_body" name="provisional_body" rows="16" class="w-full rounded-md border-gray-300 font-mono text-sm">{{ old('provisional_body', $provisionalBody) }}</textarea>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold text-gray-800">Final Letter</h3>
                <button type="button" onclick="previewLetter('final')" class="px-3 py-1.5 bg-gray-100 rounded-md text-xs hover:bg-gray-200">
                    <i class="fas fa-eye mr-1"></i> Preview
                </button>
            </div>
            <p class="text-xs text-gray-500 mb-2">Shown once the admission has been approved (principal signature appears here).</p>
            <textarea id="final_body" name="final_body" rows="16" class="w-full rounded-md border-gray-300 font-mono text-sm">{{ old('final_body', $finalBody) }}</textarea>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admission-letter-templates.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Back</a>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                <i class="fas fa-save mr-1"></i> Save
            </button>
        </div>
    </form>
</div>

<script>
    // Fetch-then-open rather than a target="_blank" form submit: the latter is blocked
    // or silently downgraded by some browsers' popup blockers, especially once a click
    // handler does anything asynchronous first. Opening a blank tab synchronously on the
    // click, then pointing it at the fetched PDF once ready, keeps it tied to the
    // original user gesture and works reliably everywhere.
    async function previewLetter(variant) {
        const newTab = window.open('', '_blank');

        if (!newTab) {
            alert('Your browser blocked the preview popup. Please allow popups for this site and try again.');
            return;
        }

        const fd = new FormData();
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        fd.append('variant', variant);
        fd.append('body', document.getElementById(variant + '_body').value);
        fd.append('admission_id', document.getElementById('preview_admission').value);

        try {
            const response = await fetch("{{ route('admission-letter-templates.preview', $academicYear) }}", {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                newTab.close();
                alert('Preview failed (HTTP ' + response.status + ').');
                return;
            }

            const blob = await response.blob();
            newTab.location = URL.createObjectURL(blob);
        } catch (e) {
            newTab.close();
            alert('Preview failed: ' + e.message);
        }
    }
</script>
@endsection
