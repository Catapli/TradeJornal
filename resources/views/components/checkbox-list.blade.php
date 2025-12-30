@props(['town' => '', 'id' => ''])

<label class="my-1 flex cursor-pointer items-start items-center gap-4 rounded-lg border border-gray-200 p-2 transition hover:bg-gray-50 has-[:checked]:bg-blue-50"
       for="town-{{ $id }}">
    <div class="flex items-center">
        <input id="town-{{ $id }}"
               class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
               type="checkbox"
               value="{{ $id }}"
               wire:model="towns_collab"
               {{-- ← clave: sincroniza con la propiedad --}} />
    </div>
    <div>
        <strong class="text-pretty font-medium text-gray-900">{{ $town }}</strong>
    </div>
</label>
