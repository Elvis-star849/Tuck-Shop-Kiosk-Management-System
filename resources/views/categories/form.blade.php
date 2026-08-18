<x-app-layout>
    <x-slot name="header">{{ $category->exists ? 'Edit category' : 'New category' }}</x-slot>
    <x-slot name="subtitle">Category name</x-slot>
    <x-slot name="title">{{ $category->exists ? 'Edit category' : 'New category' }}</x-slot>

    <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="card card-pad" style="max-width:560px;">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif
        <label class="field-label" for="name">Name</label>
        <input class="field" id="name" name="name" value="{{ old('name', $category->name) }}" required>
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn btn-ghost" href="{{ route('categories.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
