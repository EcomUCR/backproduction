<p><strong>Nombre:</strong> {{ $name }}</p>
<p><strong>Email:</strong> {{ $email }}</p>
@if ($subject)
<p><strong>Asunto:</strong> {{ $subject }}</p>
@endif
<p><strong>Mensaje:</strong></p>
<p>{{ $messageContent }}</p>