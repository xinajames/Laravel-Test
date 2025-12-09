<x-mail::message>
<p>Hi <strong>{{ $user->name }}</strong>,</p>

<p>Your report request has been processed. Here are the details:</p>

<p style="font-size: 20px; font-weight: bold; margin-top: 20px; margin-bottom: 10px;">
{{ $report_name }}
</p>

@if ($is_successful)
<p>
- <strong>File Name:</strong> {{ $file_name }}<br />
- <strong>Status:</strong> {{ $status }}<br />
- <strong>Processed Date:</strong> {{ $updated_at }}<br />
</p>
@else
<p>
- <strong>Status:</strong> {{ $status }}<br/>
- <strong>Processed Date:</strong> {{ $updated_at }}
</p>
@endif

@if ($is_successful)
<x-mail::button :url="$url">
    Go to Reports
</x-mail::button>
<p>You can view or download the report from the Reports module.</p>
@else
<p><strong>The report generation failed.</strong></p>
<p>Please try again. If the issue persists, contact your system administrator.</p>
@endif

<p>Thanks,<br>
{{ config('app.name') }} Team</p>

---

<p style="font-size: 12px; color: #6B7280; margin-top: 20px;">
This is an automatically generated email. Please do not reply to this message.
</p>
</x-mail::message>