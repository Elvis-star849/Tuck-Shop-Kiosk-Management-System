<x-app-layout>
    <x-slot name="header">Audit logs</x-slot>
    <x-slot name="subtitle">Who did what, and when</x-slot>
    <x-slot name="title">Audit logs</x-slot>
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
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $log->user?->name ?: 'System' }}</td>
                            <td>{{ $log->action ?: $log->field }}</td>
                            <td>{{ $log->description ?: (($log->field ?? 'field').' '.$log->old_value.' → '.$log->new_value) }}</td>
                            <td>{{ $log->ip_address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No audit events yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-pad">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
