<div class="group rounded-2xl border border-gray-100 bg-white p-8 shadow-xl transition-all hover:shadow-2xl">
    <div class="mb-4 flex items-center justify-between">
        <div class="{{ $color }}-400 {{ $color }}-500 rounded-xl bg-gradient-to-r p-3 text-white shadow-lg">
            {{ $icon }}
        </div>
        <div class="h-2 w-2 rounded-full bg-amber-400 opacity-0 transition-all group-hover:opacity-100"></div>
    </div>
    <h3 class="mb-1 text-3xl font-black text-gray-900 transition-all group-hover:text-amber-600">{{ $value }}</h3>
    <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
</div>
