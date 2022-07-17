@extends('layout.variants.basic')

@section('basic-content-small')
{{-- Header --}}
<h1 class="login__header font-base text-4xl">API Tokens</h1>
<p class="text-lg text-gray-600 mb-4">Toegang tot de Gumbo APIs met je eigen sleutels.</p>

<a href="{{ route('account.index') }}" class="w-full block mb-4">« Terug naar overzicht</a>

<p class="leading-loose mb-2">
  Met onderstaande API tokens heb jij toegang tot de Gumbo APIs.<br />
  Je kan een nieuwe token aanmaken, of oude verwijderen.
</p>

{{-- Existing tokens --}}
<h2 class="text-2xl font-title mb-4">Bestaande tokens</h2>

@if ($tokens->count() > 0)
  <div class="flow-root mt-6 mb-10">
    <ul role="list" class="-my-5 divide-y divide-gray-200">
      @foreach ($tokens as $token)
        <li class="py-4">
          <div class="flex items-center space-x-4">
            <div class="flex-1 min-w-0">
              <p class="text-medium text-gray-900 truncate">
                {{ $token->name }}
              </p>
              @if ($token->id === $newToken?->accessToken->id)
              <div class="px-2 py-1 bg-gray-900 text-white">
                <code data-content="plain-text-token" data-token-id="{{ $newToken->accessToken->id }}" class="text-monospace">{{ $newToken->plainTextToken }}</code>
              </div>
              @else
              <p class="text-gray-500 truncate">
                Aangemaakt op {{ $token->created_at->isoFormat('D MMM YYYY, HH:mm') }}
              </p>
              @endif
            </div>

            <form action="{{ route('account.tokens.delete', $token) }}" method="post">
              @csrf
              @method('DELETE')
              <button class="appearance-none p-4 flex items-center group">
                <x-icon icon="solid/times" class="h-4 text-gray-500 group-hover:text-red-500" />
              </button>
            </form>
          </div>
        </li>
      @endforeach
      </ul>
  </div>
@else
<div class="relative block w-full border-2 border-gray-300 border-dashed rounded-lg p-12 text-center">
  <h3 class="mt-2 text-sm font-medium text-gray-900">Geen API tokens</h3>
  <p class="mt-1 text-sm text-gray-500">Je hebt nog geen tokens aangemaakt.</p>
</div>
@endif

{{-- New token --}}
<form action="{{ route('account.tokens.store') }}" method="POST" class="form">
  @csrf
  <h2 class="text-2xl font-title mb-4">
    Nieuwe token aanmaken
  </h2>

  <div class="form__field my-0 py-0 mb-4">
    <label for="name" class="form__field-label form__field-label--required">Naam token</label>
    <input class="form__field-input form-input" autocomplete="off" required="required" minlength="2" name="name"
      type="text" id="name">
  </div>

  <x-button type="submit" size="small">
    Aanmaken
  </x-button>
</form>
@endsection
