<x-mail::message>
<p>Hi <strong>{{ $user->name }}</strong>,</p>

<p>Heads up! Here’s something coming up:</p>

<p style="font-size: 20px; font-weight: bold; margin-top: 20px; margin-bottom: 10px;">
{{ $title }}
</p>

@if ($is_manual)
<p>
- <strong>Scheduled Date:</strong> {{ $scheduled_at }}<br />
- This reminder is scheduled for <strong>today</strong>.
</p>
@else
<p>
- <strong>Scheduled Date:</strong> {{ $reference_date }}<br />
- This reminder was scheduled <strong>{{ $days_before }} day(s)</strong> in advance.
</p>
@endif

<x-mail::button :url="$url">
Go to Store
</x-mail::button>

<p>Thanks,<br>
{{ config('app.name') }} Team</p>

---

<p style="font-size: 12px; color: #6B7280; margin-top: 20px;">
This is an automatically generated email. Please do not reply to this message.
</p>
</x-mail::message>