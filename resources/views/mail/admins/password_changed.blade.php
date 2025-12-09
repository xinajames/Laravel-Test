<x-mail::message>
<p>Hi <strong>{{ $user->name }}</strong>,</p>

<p>
This is to inform you that your password on the <strong>Julie’s JFORM</strong> Portal has been reset by an administrator for security or administrative reasons.
</p>

<p>
A new temporary password has been generated for your account:
</p>

<p>
- <strong>Email:</strong> {{ $user->email }}<br>
- <strong>Temporary Password:</strong> {{ $generatedPassword }}
</p>

<x-mail::button :url="$url">
Access Your Account
</x-mail::button>

<p>
You’ll be required to change this temporary password after logging in.
</p>

<p>
If you believe this was done in error or need assistance, please contact our support team immediately.
</p>

---

<p style="font-size: 12px; color: #6B7280; margin-top: 20px;">
This is an automatically generated email. Please do not reply to this message.
</p>
</x-mail::message>