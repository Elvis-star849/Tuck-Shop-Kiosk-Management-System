<x-app-layout>
    <x-slot name="header">Categories</x-slot>
    <x-slot name="subtitle">Group products for the shop</x-slot>
    <x-slot name="title">Categories</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('categories.create') }}">New category</a>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Products</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-ghost" href="{{ route('categories.edit', $category) }}">Edit</a>
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-ghost" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $categories->links() }}</div>
    </div>
</x-app-layout>
