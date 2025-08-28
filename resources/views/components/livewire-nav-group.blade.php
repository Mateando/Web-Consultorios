@props(['label','icon'=>null,'open'=>false,'group'])
@php($isActive = $open)
<div x-data="{ open: {{ $open ? 'true':'false' }} }" class="space-y-1">
    <button type="button" @click="open=!open; $root.toggleGroup('{{ $group }}')" class="w-full flex items-center gap-2 px-2 py-2 rounded-md text-sm font-medium transition {{ $isActive ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
        <span class="w-5 h-5 text-gray-400">
            @if($icon==='menu')<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>@endif
            @if($icon==='cog')<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.757.426 1.757 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.757-2.924 1.757-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.757-.426-1.757-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.275.07 2.573-1.065z"/></svg>@endif
        </span>
        <span x-show="!sidebarCollapsed" class="flex-1 text-left">{{ $label }}</span>
        <svg x-show="!sidebarCollapsed" :class="['h-4 w-4 transition-transform', open ? 'rotate-90' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
    </button>
    <div x-show="open && !sidebarCollapsed" x-transition class="pl-5 flex flex-col space-y-1">
        {{ $slot }}
    </div>
</div>
