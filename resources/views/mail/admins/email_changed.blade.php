<x-mail::message>
<p>Hi <strong>{{ $user->name }}</strong>,</p>

<p>
Your email address on the <strong>Julie’s JFORM</strong> Portal has been successfully updated.
</p>

<p>
To ensure the security of your account, we've generated a new temporary password for you:
</p>

<p>
- <strong>Email:</strong> {{ $user->email }}<br>
- <strong>Temporary Password:</strong> {{ $generatedPassword }}
</p>

<x-mail::button :url="$url">
Access Your Account
</x-mail::button>

<p>
When you log in with your temporary password, you’ll be prompted to set a new one.
</p>

<p>
If you didn’t request this change or need any assistance, please contact our support team right away.
</p>

---

<p style="font-size: 12px; color: #6B7280; margin-top: 20px;">
This is an automatically generated email. Please do not reply to this message.
</p>
</x-mail::message>