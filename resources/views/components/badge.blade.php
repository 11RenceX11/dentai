@props(['value'])
@php
    // Plain words instead of internal codes.
    $labels = [
        'requested' => 'New request', 'scheduled' => 'Scheduled', 'completed' => 'Done', 'cancelled' => 'Cancelled',
        'emergency' => 'Urgent', 'high' => 'High priority', 'normal' => 'Normal', 'low' => 'Low',
        'pending' => 'Waiting', 'approved' => 'Approved', 'dismissed' => 'Skipped',
        'unpaid' => 'Unpaid', 'partial' => 'Partly paid', 'paid' => 'Paid', 'overdue' => 'Overdue',
        'planned' => 'Planned', 'in_progress' => 'In progress',
    ];
    $colors = [
        'emergency' => 'bg-red-100 text-red-700', 'high' => 'bg-orange-100 text-orange-700',
        'normal' => 'bg-sky-100 text-sky-700', 'low' => 'bg-slate-100 text-slate-600',
        'requested' => 'bg-amber-100 text-amber-800', 'scheduled' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-emerald-100 text-emerald-700', 'cancelled' => 'bg-slate-100 text-slate-500',
        'pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-emerald-100 text-emerald-700', 'dismissed' => 'bg-slate-100 text-slate-500',
        'paid' => 'bg-emerald-100 text-emerald-700', 'partial' => 'bg-amber-100 text-amber-800',
        'unpaid' => 'bg-slate-100 text-slate-600', 'overdue' => 'bg-red-100 text-red-700',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-full px-3 py-1 text-sm font-semibold', $colors[$value] ?? 'bg-slate-100 text-slate-600']) }}>{{ $labels[$value] ?? ucfirst(str_replace('_', ' ', $value)) }}</span>
