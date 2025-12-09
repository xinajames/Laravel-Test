<x-mail::message>
Hi **{{ $user->name }}**,

## {{ $title }}

{{ $message }}

{{--    <x-mail::button :url="$url">--}}
{{--        Go to Store--}}
{{--    </x-mail::button>--}}

Thanks,<br>
{{ config('app.name') }} Team

---

<small>
    This is an automatically generated email. Please do not reply to this message.
</small>
</x-mail::message>
