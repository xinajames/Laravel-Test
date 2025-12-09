@props([
'url',
'align' => 'center',
])

<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}"
target="_blank"
rel="noopener"
style="
background-color: #A32130;
color: #ffffff;
padding: 12px 24px;
font-size: 16px;
text-decoration: none;
display: inline-block;
border-radius: 6px;
">
{{ $slot }}
</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>