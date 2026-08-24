<div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 transition-colors dark:bg-gray-900 sm:justify-center sm:pt-0">
    <div class="flex w-full max-w-md justify-center">
        {{ $logo }}
    </div>

    <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md transition-colors dark:bg-gray-800 dark:shadow-black/30 sm:max-w-md sm:rounded-lg">
        {{ $slot }}
    </div>
</div>
