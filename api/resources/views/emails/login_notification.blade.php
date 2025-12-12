<!doctype html>
<html>
<body>
<p>Hi {{ $name }},</p>
<p>We detected a login to your account{{ $ip ? " from IP: $ip" : "" }}.</p>
<p>If this was you, no action is needed. If not, please secure your account.</p>
</body>
</html>