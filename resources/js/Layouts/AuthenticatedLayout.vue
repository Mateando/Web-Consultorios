<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const sidebarCollapsed = ref(false);
const openMenus = ref({ doctors:false, admin:false, config:false });

// Persistencia en localStorage
onMounted(() => {
    try {
        const stored = localStorage.getItem('sidebarCollapsed');
        if (stored !== null) sidebarCollapsed.value = stored === 'true';
    } catch (e) {}
});

watch(sidebarCollapsed, (val) => {
    try { localStorage.setItem('sidebarCollapsed', String(val)); } catch (e) {}
});
const page = usePage();

// Abrir automáticamente el menú doctores si la ruta coincide
onMounted(() => {
    if (route().current('doctors.*') || route().current('doctor-schedules.*')) {
        openMenus.value.doctors = true;
    }
    if (route().current('admin.*')) {
        openMenus.value.admin = true;
    }
    if (route().current('admin.config.*')) {
        openMenus.value.config = true;
    }
});

// Función para verificar si el usuario tiene alguno de los roles especificados
const hasRole = (roles) => {
    const user = page.props.auth.user;
    if (!user || !user.roles) return false;
    
    const userRoles = user.roles.map(role => role.name);
    return roles.some(role => userRoles.includes(role));
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex">
        <!-- Sidebar -->
    <aside :class="['hidden md:flex md:flex-col bg-white border-r border-gray-200 min-h-screen fixed z-[60] left-0 top-0 transition-all duration-200 overflow-visible shadow-sm', sidebarCollapsed ? 'md:w-16 sidebar-collapsed' : 'md:w-52']">
            <div class="flex items-center h-16 px-2 border-b border-gray-100 justify-between gap-1">
                <Link :href="route('dashboard')" class="flex items-center">
                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
                </Link>
                <button @click="sidebarCollapsed = !sidebarCollapsed" :class="['relative z-[70] flex-shrink-0 inline-flex items-center justify-center h-9 w-9 rounded-md transition border border-gray-300 bg-white/90 backdrop-blur', sidebarCollapsed ? 'shadow-sm hover:bg-white' : 'hover:bg-gray-100']" :title="sidebarCollapsed ? 'Expandir menú' : 'Colapsar menú'">
                    <svg xmlns="http://www.w3.org/2000/svg" :class="['h-8 w-8 text-gray-600 transition-transform duration-200 drop-shadow-sm', sidebarCollapsed ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </button>
            </div>
            <nav class="flex-1 flex flex-col px-2 py-4 gap-1 overflow-y-auto overflow-visible">
                <div class="relative group" >
                    <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6m-6 0H7m6 0v6m0 0h6m-6 0H7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span v-if="!sidebarCollapsed" class="ml-2">Dashboard</span>
                        </span>
                    </NavLink>
                    <div v-if="sidebarCollapsed" class="tooltip">Dashboard</div>
                </div>
                <div class="relative group">
                    <NavLink :href="route('appointments.index')" :active="route().current('appointments.*')">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span v-if="!sidebarCollapsed" class="ml-2">Citas</span>
                        </span>
                    </NavLink>
                    <div v-if="sidebarCollapsed" class="tooltip">Citas</div>
                </div>
                <div v-if="hasRole(['administrador', 'medico', 'recepcionista'])" class="relative group">
                    <NavLink :href="route('patients.index')" :active="route().current('patients.*')">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 3.13a4 4 0 010 7.75M8 3.13a4 4 0 000 7.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span v-if="!sidebarCollapsed" class="ml-2">Pacientes</span>
                        </span>
                    </NavLink>
                    <div v-if="sidebarCollapsed" class="tooltip">Pacientes</div>
                </div>
                <!-- Menú desplegable Doctores -->
                <div v-if="hasRole(['administrador','recepcionista','medico'])" class="relative group">
                    <button type="button" @click="!sidebarCollapsed && (openMenus.doctors = !openMenus.doctors)" :class="['w-full text-left px-2 py-2 rounded-md transition flex items-center gap-2', (route().current('doctors.*')||route().current('doctor-schedules.*')) ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50']">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span v-if="!sidebarCollapsed" class="flex-1">Doctores</span>
                        <svg v-if="!sidebarCollapsed" :class="['h-4 w-4 transition-transform', openMenus.doctors ? 'rotate-90' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <!-- Submenú expandido (sidebar ancho) -->
                    <transition name="fade" mode="out-in">
                        <div v-show="openMenus.doctors && !sidebarCollapsed" class="mt-1 pl-6 flex flex-col space-y-1">
                            <NavLink v-if="hasRole(['administrador','recepcionista'])" :href="route('doctors.index')" :active="route().current('doctors.*')">Lista</NavLink>
                            <NavLink v-if="hasRole(['administrador','medico'])" :href="route('doctor-schedules.index')" :active="route().current('doctor-schedules.*')">Horarios</NavLink>
                        </div>
                    </transition>
                    <!-- Flyout cuando está colapsado -->
                    <div v-if="sidebarCollapsed" class="submenu-flyout">
                        <div class="flex flex-col gap-1">
                            <Link v-if="hasRole(['administrador','recepcionista'])" :href="route('doctors.index')" class="submenu-item" :class="route().current('doctors.*') ? 'active' : ''">Lista</Link>
                            <Link v-if="hasRole(['administrador','medico'])" :href="route('doctor-schedules.index')" class="submenu-item" :class="route().current('doctor-schedules.*') ? 'active' : ''">Horarios</Link>
                        </div>
                    </div>
                </div>
                <!-- Menú desplegable Administración -->
                <div v-if="hasRole(['administrador'])" class="relative group">
                    <button type="button" @click="!sidebarCollapsed && (openMenus.admin = !openMenus.admin)" :class="['w-full text-left px-2 py-2 rounded-md transition flex items-center gap-2', route().current('admin.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50']">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h18" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span v-if="!sidebarCollapsed" class="flex-1">Administración</span>
                        <svg v-if="!sidebarCollapsed" :class="['h-4 w-4 transition-transform', openMenus.admin ? 'rotate-90' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <!-- Submenú (sidebar ancho) -->
                    <transition name="fade" mode="out-in">
                        <div v-show="openMenus.admin && !sidebarCollapsed" class="mt-1 pl-6 flex flex-col space-y-1">
                            <NavLink :href="route('admin.index')" :active="route().current('admin.index')">Generales</NavLink>
                            <NavLink :href="route('admin.users')" :active="route().current('admin.users')">Gestión Usuarios</NavLink>
                            <NavLink :href="route('admin.specialties')" :active="route().current('admin.specialties')">Especialidades Médicas</NavLink>
                            <NavLink :href="route('admin.reports')" :active="route().current('admin.reports')">Reportes</NavLink>
                            <NavLink :href="route('admin.config.clinic')" :active="route().current('admin.config.clinic')">Consultorio</NavLink>
                        </div>
                    </transition>
                    <!-- Flyout colapsado -->
                    <div v-if="sidebarCollapsed" class="submenu-flyout">
                        <div class="flex flex-col gap-1">
                            <Link :href="route('admin.index')" class="submenu-item" :class="route().current('admin.index') ? 'active' : ''">Generales</Link>
                            <Link :href="route('admin.users')" class="submenu-item" :class="route().current('admin.users') ? 'active' : ''">Gestión Usuarios</Link>
                            <Link :href="route('admin.specialties')" class="submenu-item" :class="route().current('admin.specialties') ? 'active' : ''">Especialidades Médicas</Link>
                            <Link :href="route('admin.reports')" class="submenu-item" :class="route().current('admin.reports') ? 'active' : ''">Reportes</Link>
                            <Link :href="route('admin.config.clinic')" class="submenu-item" :class="route().current('admin.config.clinic') ? 'active' : ''">Consultorio</Link>
                        </div>
                    </div>
                </div>
                <!-- Menú desplegable Configuración -->
                <div v-if="hasRole(['administrador'])" class="relative group">
                    <button type="button" @click="!sidebarCollapsed && (openMenus.config = !openMenus.config)" :class="['w-full text-left px-2 py-2 rounded-md transition flex items-center gap-2', route().current('admin.config.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50']">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.757.426 1.757 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.757-2.924 1.757-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.757-.426-1.757-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.275.07 2.573-1.065z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span v-if="!sidebarCollapsed" class="flex-1">Configuración</span>
                        <svg v-if="!sidebarCollapsed" :class="['h-4 w-4 transition-transform', openMenus.config ? 'rotate-90' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <transition name="fade" mode="out-in">
                        <div v-show="openMenus.config && !sidebarCollapsed" class="mt-1 pl-6 flex flex-col space-y-1">
                            <NavLink :href="route('admin.config.patient-types')" :active="route().current('admin.config.patient-types')">Tipos de Paciente</NavLink>
                            <NavLink :href="route('admin.config.insurance-providers')" :active="route().current('admin.config.insurance-providers')">Obras Sociales</NavLink>
                            <NavLink :href="route('admin.config.countries')" :active="route().current('admin.config.countries')">Países</NavLink>
                        </div>
                    </transition>
                    <div v-if="sidebarCollapsed" class="submenu-flyout">
                        <div class="flex flex-col gap-1">
                            <Link :href="route('admin.config.patient-types')" class="submenu-item" :class="route().current('admin.config.patient-types') ? 'active' : ''">Tipos de Paciente</Link>
                            <Link :href="route('admin.config.insurance-providers')" class="submenu-item" :class="route().current('admin.config.insurance-providers') ? 'active' : ''">Obras Sociales</Link>
                            <Link :href="route('admin.config.countries')" class="submenu-item" :class="route().current('admin.config.countries') ? 'active' : ''">Países</Link>
                        </div>
                    </div>
                </div>
            </nav>
    </aside>

        <!-- Contenido principal y barra superior para móvil -->
    <div :class="['flex-1 flex flex-col min-h-screen transition-all duration-200', sidebarCollapsed ? 'md:ml-16' : 'md:ml-52']">
            <!-- Topbar: usuario a la derecha en desktop, menú hamburguesa en móvil -->
            <nav :class="['flex items-center h-16 px-4 bg-white border-b border-gray-100 fixed z-50 top-0 left-0 right-0 transition-all duration-200', sidebarCollapsed ? 'md:ml-16' : 'md:ml-52']">
                <!-- Usuario en desktop -->
                <div class="hidden md:block absolute right-0">
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <span class="inline-flex rounded-md">
                                <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                                    <img v-if="$page.props.auth.user.profile_photo_path" :src="'/storage/' + $page.props.auth.user.profile_photo_path" alt="Foto de perfil" class="h-8 w-8 rounded-full object-cover border mr-2" />
                                    <span v-else class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 mr-2">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                    {{ $page.props.auth.user.name }}
                                    <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </span>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
                <!-- Hamburger en móvil -->
                <div class="md:hidden">
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            <!-- Menú hamburguesa solo en móvil -->
            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="md:hidden bg-white border-b border-gray-100">
                <div class="space-y-1 pb-3 pt-2 px-4">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('appointments.index')" :active="route().current('appointments.*')">Citas</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="hasRole(['administrador', 'medico', 'recepcionista'])" :href="route('patients.index')" :active="route().current('patients.*')">Pacientes</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="hasRole(['administrador', 'recepcionista'])" :href="route('doctors.index')" :active="route().current('doctors.*')">Doctores</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="hasRole(['administrador', 'medico'])" :href="route('doctor-schedules.index')" :active="route().current('doctor-schedules.*')">Horarios</ResponsiveNavLink>
                    <ResponsiveNavLink v-if="hasRole(['administrador'])" :href="route('admin.index')" :active="route().current('admin.*')">Administración</ResponsiveNavLink>
                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex items-center gap-2 mb-2">
                            <img v-if="$page.props.auth.user.profile_photo_path" :src="'/storage/' + $page.props.auth.user.profile_photo_path" alt="Foto de perfil" class="h-10 w-10 rounded-full object-cover border" />
                            <span v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div>
                                <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                                <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Perfil</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Cerrar sesión</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </div>
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="md:hidden">
                    <div class="space-y-1 pb-3 pt-2 px-4">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('appointments.index')" :active="route().current('appointments.*')">Citas</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="hasRole(['administrador', 'medico', 'recepcionista'])" :href="route('patients.index')" :active="route().current('patients.*')">Pacientes</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="hasRole(['administrador', 'recepcionista'])" :href="route('doctors.index')" :active="route().current('doctors.*')">Doctores</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="hasRole(['administrador', 'medico'])" :href="route('doctor-schedules.index')" :active="route().current('doctor-schedules.*')">Horarios</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="hasRole(['administrador'])" :href="route('admin.index')" :active="route().current('admin.*')">Administración</ResponsiveNavLink>
                        <div class="border-t border-gray-200 pt-4">
                            <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                            <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                            <div class="mt-3 space-y-1">
                                <ResponsiveNavLink :href="route('profile.edit')">Perfil</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('logout')" method="post" as="button">Cerrar sesión</ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            <!-- Page Heading -->
            <header class="bg-white shadow mt-16" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>
            <!-- Overlay móvil cuando menú hamburguesa abierto -->
            <div v-if="showingNavigationDropdown" class="fixed inset-0 bg-black/40 md:hidden animate-fade" @click="showingNavigationDropdown=false"></div>
            <!-- Page Content -->
            <main class="flex-1 mt-16">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.tooltip { position:absolute; left:100%; top:50%; transform:translateY(-50%); margin-left:0.5rem; padding:0.25rem 0.5rem; border-radius:0.25rem; background:#1f2937; color:#fff; font-size:0.75rem; white-space:nowrap; opacity:0; translate:0; transition:opacity .15s ease, transform .15s ease; pointer-events:none; z-index:60; box-shadow:0 2px 4px rgba(0,0,0,.3); }
/* Solo mostrar tooltips cuando está colapsado */
.sidebar-collapsed .group:hover > .tooltip { opacity:1; transform:translateY(-50%) scale(1); }
/* Fallback: si algún contenedor tiene overflow hidden, elevar tooltip */
.sidebar-collapsed .tooltip { z-index:9999; }
.sidebar-collapsed .group { overflow:visible !important; }
.sidebar-collapsed nav, .sidebar-collapsed aside { overflow:visible !important; }
.animate-fade { animation: fadeIn .15s ease-out; }
@keyframes fadeIn { from { opacity:0; } to { opacity:.4; } }
/* Submenú */
.submenu-flyout { position:absolute; left:100%; top:0; margin-left:0.25rem; background:#ffffff; border:1px solid #e5e7eb; padding:0.5rem 0.75rem; border-radius:0.5rem; box-shadow:0 4px 12px rgba(0,0,0,.08); z-index:9999; width:150px; opacity:0; transform:translateY(4px); pointer-events:none; transition:opacity .15s ease, transform .15s ease; }
.group:hover > .submenu-flyout { opacity:1; transform:translateY(0); pointer-events:auto; }
.submenu-item { display:block; font-size:0.75rem; line-height:1rem; padding:0.4rem 0.5rem; border-radius:0.375rem; color:#4b5563; font-weight:500; }
.submenu-item:hover { background:#f3f4f6; color:#111827; }
.submenu-item.active { background:#e5e7eb; color:#111827; }
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity:0; }
</style>
