<template>
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h2 class="text-lg font-bold mb-4">Autenticación de dos factores (Google Authenticator)</h2>
        <div v-if="enabled" class="mb-4">
            <p class="text-green-600 font-semibold mb-2">2FA está activado en tu cuenta.</p>
            <form @submit.prevent="disable2fa">
                    <PrimaryButton type="submit" class="bg-red-600 hover:bg-red-700">Desactivar 2FA</PrimaryButton>
            </form>
        </div>
        <div v-else>
            <p class="mb-2">Escanea este código QR con Google Authenticator o una app compatible:</p>
            <div class="flex justify-center mb-4">
                <div v-html="qr"></div>
            </div>
            <p class="mb-2">O ingresa manualmente esta clave: <span class="font-mono bg-gray-100 px-2 py-1">{{ secret }}</span></p>
            <form @submit.prevent="enable2fa" class="mt-4">
                <label class="block mb-2">Código de 6 dígitos de la app:</label>
                <input v-model="code" type="text" maxlength="6" class="border rounded px-2 py-1 mb-2 w-full" required />
                <input type="hidden" :value="secret" name="secret" />
                    <PrimaryButton type="submit" class="bg-green-600 hover:bg-green-700">Activar 2FA</PrimaryButton>
            </form>
        </div>
        <div v-if="$page.props.flash.error" class="text-red-600 mt-2">{{ $page.props.flash.error }}</div>
        <div v-if="$page.props.flash.success" class="text-green-600 mt-2">{{ $page.props.flash.success }}</div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
const props = defineProps({
    qr: String,
    secret: String,
    enabled: Boolean,
});
const code = ref('');
const enable2fa = () => {
    router.post(route('profile.2fa.enable'), { code: code.value, secret: props.secret }, { preserveScroll: true });
};
const disable2fa = () => {
    router.post(route('profile.2fa.disable'), {}, { preserveScroll: true });
};
</script>
