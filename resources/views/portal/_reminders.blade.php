{{-- Pop-up reminders while the page is open. Phone alarms come from the "Add to my calendar" download. --}}
@php
    $items = $doses->where('taken', false)->map(fn ($d) => [
        'time' => $d['time'],
        'text' => "{$d['prescription']->medication_name} — {$d['prescription']->dosage}",
    ])->values();
@endphp
@if ($items->isNotEmpty())
<div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-blue-50 px-5 py-4">
    <p class="text-blue-900">🔔 Get a pop-up when it’s time to take your medicine <span class="text-blue-700">(while this page is open)</span>.</p>
    <button id="enable-reminders" type="button" class="btn btn-primary btn-sm">Turn on reminders</button>
</div>
<script>
    (() => {
        const doses = @json($items);
        const btn = document.getElementById('enable-reminders');
        if (!('Notification' in window)) { btn.remove(); return; }

        function schedule() {
            const now = new Date();
            doses.forEach(d => {
                const [h, m] = d.time.split(':').map(Number);
                const at = new Date(); at.setHours(h, m, 0, 0);
                const wait = at - now;
                if (wait > 0) setTimeout(() => new Notification('Time for your medicine', { body: d.text }), wait);
            });
        }
        function on() { btn.textContent = 'Reminders on ✓'; btn.disabled = true; schedule(); }

        if (Notification.permission === 'granted') on();
        btn.addEventListener('click', async () => { if (await Notification.requestPermission() === 'granted') on(); });
    })();
</script>
@endif
