@props(['routeName'])
@php($active = request()->routeIs($routeName))
<a href="{{ route($routeName) }}" class="text-xs px-2 py-1 rounded-md font-medium {{ $active ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">{{ $slot }}</a>
