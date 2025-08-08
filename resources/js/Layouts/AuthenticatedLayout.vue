<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();

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
        <!-- Sidebar fijo en desktop -->
        <aside class="hidden md:flex md:flex-col md:w-52 bg-white border-r border-gray-200 min-h-screen fixed z-50 left-0 top-0">
        <div class="flex items-center h-16 px-6 border-b border-gray-100">
            <Link :href="route('dashboard')">
                <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
            </Link>
        </div>
        <nav class="flex-1 flex flex-col px-2 py-4 gap-1">
            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6m-6 0H7m6 0v6m0 0h6m-6 0H7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Dashboard
                </span>
            </NavLink>
            <NavLink :href="route('appointments.index')" :active="route().current('appointments.*')">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Citas
                </span>
            </NavLink>
            <NavLink v-if="hasRole(['administrador', 'medico', 'recepcionista'])" :href="route('patients.index')" :active="route().current('patients.*')">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 3.13a4 4 0 010 7.75M8 3.13a4 4 0 000 7.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Pacientes
                </span>
            </NavLink>
            <NavLink v-if="hasRole(['administrador', 'recepcionista'])" :href="route('doctors.index')" :active="route().current('doctors.*')">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Doctores
                </span>
            </NavLink>
            <NavLink v-if="hasRole(['administrador', 'medico'])" :href="route('doctor-schedules.index')" :active="route().current('doctor-schedules.*')">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 17l4 4 4-4m-4-5v9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Horarios
                </span>
            </NavLink>
            <NavLink v-if="hasRole(['administrador'])" :href="route('admin.index')" :active="route().current('admin.*')">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h18" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Administración
                </span>
            </NavLink>
        </nav>
        </aside>

        <!-- Contenido principal y barra superior para móvil -->
    <div class="flex-1 flex flex-col min-h-screen md:ml-52">
            <!-- Topbar: usuario a la derecha en desktop, menú hamburguesa en móvil -->
            <nav class="flex items-center h-16 px-4 bg-white border-b border-gray-100 fixed z-50 top-0 left-0 right-0 md:ml-52">
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
            <!-- Page Content -->
            <main class="flex-1 mt-16">
                <slot />
            </main>
        </div>
    </div>
</template>
