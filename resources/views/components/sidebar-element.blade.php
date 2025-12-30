@props(['route', 'icon', 'name'])
<li class="relative flex h-10 items-center justify-center"
    :class="{ 'bg-secondaryhover': {{ request()->routeIs($route) ? 'true' : 'false' }} }"
    x-data="{ open: false }"
    @mouseenter="open = true"
    @mouseleave="open = false">
    <a class="group flex h-full w-full items-center justify-center rounded-lg text-gray-900"
       href="{{ route($route) }}">
        <i class="{{ $icon }} text-white"></i>
    </a>
    <span class="fixed left-full z-50 w-max min-w-40 rounded-r-md bg-secondary px-4 py-3 ps-3 text-white"
          x-transition
          x-cloak
          x-show="open"
          :class="{ 'bg-secondaryhover': {{ request()->routeIs($route) ? 'true' : 'false' }} }"><a href="{{ route($route) }}">{{ $name }}</a>
    </span>
</li>
