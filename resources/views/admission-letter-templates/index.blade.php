@extends('components.app-layout')

@section('title', 'Admission Letter')
@section('subtitle', 'Customize the admission letter text sent to applicants, per academic year')

@section('content')
<div class="py-4">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Academic Year</th>
                    <th class="px-4 py-3 text-left">Letter</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($academicYears as $year)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $year->name }}</td>
                        <td class="px-4 py-3">
                            @if(isset($templates[$year->id]))
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Customized</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-500">Using default text</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admission-letter-templates.edit', $year) }}" class="text-primary-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
