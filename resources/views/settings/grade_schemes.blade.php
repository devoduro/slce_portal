@extends('components.app-layout')

@section('title', 'Grade Schemes')
@section('subtitle', 'Manage grade schemes and grading systems')

@section('content')
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Grade Schemes') }}
            </h2>
            <a href="{{ route('settings.grade-schemes.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-md transition-colors">
                <i class="fas fa-plus mr-2"></i>
                {{ __('Add New Grade Scheme') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('layouts.messages')

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($gradeSchemes->isEmpty())
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <i class="fas fa-clipboard-list text-6xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Grade Schemes Found</h3>
                            <p class="text-gray-500">Get started by creating your first grade scheme.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach($gradeSchemes as $scheme)
                                <div class="bg-white rounded-lg border shadow-sm hover:shadow-md transition-shadow">
                                    <div class="px-6 py-4 border-b bg-gray-50">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900">{{ $scheme->name }}</h3>
                                                <p class="text-sm text-gray-500">{{ $scheme->description }}</p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                @if($scheme->is_default)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i> Default
                                                    </span>
                                                @endif
                                                <div class="relative" x-data="{ open: false }">
                                                    <button @click="open = !open" class="text-gray-400 hover:text-gray-600">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10" style="display: none;">
                                                        <div class="py-1">
                                                            <a href="{{ route('settings.grade-schemes.edit', $scheme->id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                <i class="fas fa-edit mr-2"></i> Edit
                                                            </a>
                                                            @if(!$scheme->is_default)
                                                                <form action="{{ route('settings.grade-schemes.set-default', $scheme->id) }}" method="POST">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                        <i class="fas fa-check-circle mr-2"></i> Set as Default
                                                                    </button>
                                                                </form>
                                                                <form action="{{ route('settings.grade-schemes.destroy', $scheme->id) }}" method="POST"
                                                                      onsubmit="return confirm('Are you sure you want to delete this grade scheme? This action cannot be undone.');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                                        <i class="fas fa-trash mr-2"></i> Delete
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead>
                                                    <tr>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Range</th>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">GPA</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach($scheme->grades as $grade)
                                                        <tr>
                                                            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $grade->letter }}</td>
                                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ number_format($grade->min_score, 1) }}+</td>
                                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ number_format($grade->gpa_value, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
@endsection
