@props(['status'])

<span class="badge badge-{{ $status }}">{{ invoice_status_label($status) }}</span>
