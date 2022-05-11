<?php
$activityName = $album->activity?->name;

$stats = array_filter([
  ['icon' => 'solid/calendar-alt', 'label' => $activityName ? "Hoort bij {$activityName}" : null],
  ['icon' => 'solid/user', 'label' => $album->user?->public_name],
  ['icon' => 'solid/images', 'label' => trans_choice(":count photo|:count photos", $album->photos->count())],
  ['icon' => 'solid/pencil-alt', 'label' => $album->updated_at->isoFormat('D MMM YYYY') ],
], fn ($row) => !empty($row['label']));
?>
<x-page :title="[$album->name, 'Galerij']">
  <x-sections.header
    :title="$album->name"
    :crumbs="['/' => 'Home', '/gallery' => 'Galerij']"
    :stats="$stats"
    >
    <x-slot name="buttons">
      @can('update', $album)
      <x-button color="" size="small" href="{{ route('gallery.album.edit', $album) }}" class="flex items-center">
        <x-icon icon="solid/pencil-alt" class="h-4" />
        <span class="ml-2 lg:sr-only">Bewerken</span>
      </x-button>
      @endcan

      @can('upload', $album)
      <x-button color="primary" size="small" href="{{ route('gallery.album.upload', $album) }}" class="flex items-center">
        <x-icon icon="solid/upload" class="h-4 mr-2" />
        Uploaden
      </x-button>
      @endcan
    </x-slot>
  </x-sections.header>

<x-container>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
    @forelse ($album->photos as $photo)
      <x-gallery.photo-tile :photo="$photo" />
    @empty
      <div class="border-2 border-gray-200 rounded-lg p-8 text-center col-span-4">
        <p class="text-gray-400 text-4xl">
          Er zijn nog geen foto's in dit album
        </p>
      </div>
    @endforelse
  </div>
</x-container>
</x-page>