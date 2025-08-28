@props(['routeName','pattern'=>null])
@php($active = request()->routeIs($pattern ?: $routeName))
<a href="{{ route($routeName) }}" class="block px-2 py-1 rounded text-sm font-medium {{ $active ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">{{ $slot }}</a>
