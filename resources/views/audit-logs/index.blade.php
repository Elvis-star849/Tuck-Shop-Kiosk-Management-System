<x-app-layout>
    <x-slot name="header">Activity log</x-slot>
    <x-slot name="subtitle">Every change in this shop. Visible to admins only.</x-slot>
    <x-slot name="title">Activity log</x-slot>
    <x-slot name="actions">
        <form method="GET" class="filters">
            <input class="field" type="search" name="search" value="{{ request('search') }}" placeholder="Search actions">
            <button class="btn btn-ghost" type="submit">Search</button>
        </form>
    </x-slot>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Who</th>
                        <th>Action</th>
                        <th>Change</th>
                        <th>From</th>
                        <th>To</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $log->user?->name ?: 'System' }}</td>
                            <td>{{ $log->action ?: $log->field }}</td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->old_value ?: '—' }}</td>
                            <td>{{ $log->new_value ?: '—' }}</td>
                            <td>{{ $log->ip_address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No activity recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
