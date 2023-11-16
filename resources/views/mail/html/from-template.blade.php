@component('mail::message')
{{-- Subcopy --}}
@slot('subcopy')
@if($footnote)
{{ $footnote }}
@else
Dit is een automatisch bericht vanuit de website, reageren is niet mogelijk.
@endif
<!-- Template ID: {{ $template->id }} / {{ $template->label }} -->
@endslot

{{-- The actual body --}}
{{ $body }}
@endcomponent
