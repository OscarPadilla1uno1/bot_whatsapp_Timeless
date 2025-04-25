@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === config('app.name'))
<img src="{{ asset('svg/logo.svg') }}" class="logo" alt="La Campaña" width="100" height="100" style="border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
