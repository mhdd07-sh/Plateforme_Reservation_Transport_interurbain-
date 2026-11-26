@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{ $slot }}<span style="display: inline-block; width: 9px; height: 9px; margin-left: 5px; border-radius: 50%; background-color: #fdc425;"></span>
</a>
</td>
</tr>