<x-app-layout>
    <x-slot name="header">Expenses</x-slot>
    <x-slot name="subtitle">Shop running costs</x-slot>
    <x-slot name="title">Expenses</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('expenses.create') }}">New expense</a>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date->format('d M Y') }}</td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ money($expense->amount) }}</td>
                            <td>{{ $expense->user?->name }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost" href="{{ route('expenses.edit', $expense) }}">Edit</a>
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-ghost" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $expenses->links() }}</div>
    </div>
</x-app-layout>
