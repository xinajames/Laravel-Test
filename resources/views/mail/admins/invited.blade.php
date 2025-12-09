<x-mail::message>
<p>Hi <strong>{{ $user->name }}</strong>,</p>

<p>
You've been invited to join the <strong>Julie’s JFORM</strong> Portal.
</p>

<p>
We're excited to have you on board! Here’s your account information to get started:
</p>

<p>
- <strong>Email:</strong> {{ $user->email }}<br>
- <strong>Temporary Password:</strong> {{ $generatedPassword }}
</p>

<x-mail::button :url="$url">
Log In to Your Account
</x-mail::button>

<p>
You’ll be prompted to change your temporary password upon your first login.
</p>

<p>
If you have any questions or need help, feel free to reach out to our support team.
</p>

---

<p style="font-size: 12px; color: #6B7280; margin-top: 20px;">
This is an automatically generated email. Please do not reply to this message.
</p>
</x-mail::message>