<script setup>
import { ref, onBeforeUnmount, watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    profile_photo: null,
});

const fileInput = ref(null);

function openFileSelector() {
    if (fileInput.value) {
        fileInput.value.click();
    }
}

// previewUrl guarda la URL temporal creada con createObjectURL
const previewUrl = ref(null);

function handlePhotoChange(e) {
    const file = e.target.files[0];
    form.profile_photo = file || null;

    // limpiar previas URLs
    if (previewUrl.value) {
        try { URL.revokeObjectURL(previewUrl.value); } catch (err) {}
        previewUrl.value = null;
    }

    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    }
}

// Si el form.profile_photo se resetea desde fuera (onSuccess), limpiar la previsualización
watch(() => form.profile_photo, (val) => {
    if (!val && previewUrl.value) {
        try { URL.revokeObjectURL(previewUrl.value); } catch (err) {}
        previewUrl.value = null;
    }
});

onBeforeUnmount(() => {
    if (previewUrl.value) {
        try { URL.revokeObjectURL(previewUrl.value); } catch (err) {}
        previewUrl.value = null;
    }
});

function submitForm() {
    form.submit('post', route('profile.update'), {
        data: {
            name: form.name,
            email: form.email,
            profile_photo: form.profile_photo,
            _method: 'PATCH',
        },
        preserveScroll: true,
        onSuccess: () => form.reset('profile_photo'),
        forceFormData: true,
    });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="submitForm"
            class="mt-6 space-y-6"
            enctype="multipart/form-data"
        >

            <div class="flex items-start gap-4">
                <div>
                    <InputLabel for="profile_photo" value="Foto de perfil" />
                    <!-- mostrar la miniatura encima del botón -->
                    <div class="mt-2">
                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 transform scale-95"
                            enter-to-class="opacity-100 transform scale-100"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 transform scale-100"
                            leave-to-class="opacity-0 transform scale-95"
                        >
                            <img
                                v-if="previewUrl || user.profile_photo_path"
                                :src="previewUrl || ('/storage/' + user.profile_photo_path)"
                                alt="Foto de perfil"
                                class="h-24 w-24 rounded-full object-cover border"
                            />
                        </Transition>
                    </div>

                    <!-- input file oculto, se controla con el botón -->
                    <input
                        id="profile_photo"
                        ref="fileInput"
                        type="file"
                        class="hidden"
                        @change="handlePhotoChange"
                        accept="image/*"
                    />

                    <div class="flex items-center gap-2 mt-3">
                        <PrimaryButton type="button" @click.prevent="openFileSelector">Seleccionar imagen</PrimaryButton>
                        <span class="text-sm text-gray-600" v-if="form.profile_photo">{{ form.profile_photo.name }}</span>
                    </div>

                    <InputError class="mt-2" :message="form.errors.profile_photo" />
                </div>
            </div>
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton type="submit" :disabled="form.processing">Guardar</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
