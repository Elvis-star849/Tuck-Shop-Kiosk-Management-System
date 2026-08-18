<x-app-layout>
    <x-slot name="header">{{ $expense->exists ? 'Edit expense' : 'New expense' }}</x-slot>
    <x-slot name="subtitle">Transport, rent, electricity, and other costs</x-slot>
    <x-slot name="title">{{ $expense->exists ? 'Edit expense' : 'New expense' }}</x-slot>

    <form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="card card-pad" style="max-width:720px;">
        @csrf
        @if ($expense->exists)
            @method('PUT')
        @endif
        <div class="form-grid">
            <div>
                <label class="field-label" for="expense_date">Date</label>
                <input class="field" id="expense_date" type="date" name="expense_date" value="{{ old('expense_date', optional($expense->expense_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="field-label" for="category">Category</label>
                <select class="field" id="category" name="category" required>
                    @foreach (\App\Models\Expense::CATEGORIES as $category)
                        <option value="{{ $category }}" @selected(old('category', $expense->category ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="full">
                <label class="field-label" for="description">Description</label>
                <input class="field" id="description" name="description" value="{{ old('description', $expense->description) }}" required>
            </div>
            <div>
                <label class="field-label" for="amount">Amount</label>
                <input class="field" id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expense->amount) }}" required>
            </div>
        </div>
        <div class="actions" style="margin-top:18px;">
            <button class="btn btn-primary" type="submit">Save expense</button>
            <a class="btn btn-ghost" href="{{ route('expenses.index') }}">Cancel</a>
        </div>
    </form>
</x-app-layout>
