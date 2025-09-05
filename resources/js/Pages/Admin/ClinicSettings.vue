<template>
  <Head title="Datos del Consultorio" />
  <AuthenticatedLayout>

    <section class="py-8 max-w-6xl mx-auto px-4">
      <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
        <div class="mb-6">
          <h2 class="text-lg font-medium text-gray-900">Configuración</h2>
          <p class="mt-1 text-sm text-gray-600">Editar los datos del consultorio</p>
        </div>
        <div v-if="successMessage" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded">
          {{ successMessage }}
        </div>

        <div v-if="hasErrors" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded">
          Error al guardar. Revisa los campos marcados.
        </div>
        <div v-if="errors.general" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded">
          {{ Array.isArray(errors.general) ? errors.general[0] : errors.general }}
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-6 max-w-2xl">
        <div>
          <InputLabel for="name" value="Nombre" />
          <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
          <InputError class="mt-2" :message="getError('name')" />
        </div>

        <div>
          <InputLabel for="address" value="Dirección" />
          <TextInput id="address" v-model="form.address" class="mt-1 block w-full" />
          <InputError class="mt-2" :message="getError('address')" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="phone" value="Teléfono" />
            <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
            <InputError class="mt-2" :message="getError('phone')" />
          </div>
          <div>
            <InputLabel for="email" value="Email" />
            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
            <InputError class="mt-2" :message="getError('email')" />
          </div>
        </div>

        <div>
          <InputLabel for="tax_id" value="CUIT / NIF" />
          <TextInput id="tax_id" v-model="form.tax_id" class="mt-1 block w-full" />
          <InputError class="mt-2" :message="getError('tax_id')" />
        </div>

        <div>
          <InputLabel for="footer_notes" value="Notas pie" />
          <textarea id="footer_notes" v-model="form.footer_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
          <InputError class="mt-2" :message="getError('footer_notes')" />
        </div>

        <div>
          <InputLabel value="Logo (se usará en encabezados y favicon)" />
          <div class="mt-2">
            <Transition
              enter-active-class="transition ease-out duration-200"
              enter-from-class="opacity-0 transform scale-95"
              enter-to-class="opacity-100 transform scale-100"
              leave-active-class="transition ease-in duration-150"
              leave-from-class="opacity-100 transform scale-100"
              leave-to-class="opacity-0 transform scale-95"
            >
              <div v-if="logoPreview || clinic.logo_url" class="mb-3 h-24 w-48 flex items-center justify-center border bg-white">
                <img v-if="logoPreview" :src="logoPreview" alt="Preview" class="h-full object-contain" />
                <img v-else-if="clinic.logo_url" :src="clinic.logo_url" alt="Logo actual" class="h-full object-contain" />
              </div>
            </Transition>

            <input ref="fileInput" class="hidden" @change="onFileChange" type="file" accept="image/*" />
            <div class="flex items-center gap-2">
              <PrimaryButton type="button" @click.prevent="chooseFile">Seleccionar imagen</PrimaryButton>
              <SecondaryButton type="button" class="bg-red-50 text-red-600" @click="removeLogo">Eliminar</SecondaryButton>
              <span class="text-sm text-gray-600" v-if="logoFile">{{ logoFile.name }}</span>
            </div>

            <InputError class="mt-2" :message="getError('logo')" />
          </div>
        </div>

        <div class="flex items-center gap-4">
          <PrimaryButton type="submit" :disabled="processing">{{ processing ? 'Guardando...' : 'Guardar' }}</PrimaryButton>
          <SecondaryButton type="button" @click="reset">Restablecer</SecondaryButton>
        </div>
        </form>
      </div>
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import axios from 'axios'

const page = usePage()
// Make clinic reactive so updates from the server are reflected in the UI.
// Use a defensive initialization: read from Inertia props when available and
// keep clinic in a ref so we can watch it. Use a reactive object for the form
// so v-model works consistently with nested properties.
const clinic = ref({})

const form = reactive({
  name: '',
  address: '',
  phone: '',
  email: '',
  tax_id: '',
  footer_notes: '',
})

const processing = ref(false)
const errors = ref({})
const successMessage = ref('')

const hasErrors = computed(() => Object.keys(errors.value || {}).length > 0)

const getError = (field) => {
  const v = errors.value?.[field]
  if (!v) return ''
  return Array.isArray(v) ? v[0] : v
}

const submit = async () => {
  processing.value = true
  errors.value = {}
  successMessage.value = ''

  try {
    const url = route('admin.config.clinic.update')
  // If there's a file, send multipart/form-data
  const payload = new FormData();
  Object.keys(form).forEach(k => payload.append(k, form[k] ?? ''));
  // logoFile and removeLogoFlag are refs — use .value
  if (logoFile.value) payload.append('logo', logoFile.value);
  if (removeLogoFlag.value) payload.append('remove_logo', '1');

  // Let the browser set the Content-Type (including boundary) for multipart/form-data
  // Añadir token CSRF explícitamente dentro del FormData por si el header no llega
  const csrfMeta = document.head.querySelector('meta[name="csrf-token"]')
  if (csrfMeta && !payload.has('_token')) {
    payload.append('_token', csrfMeta.content)
  }

  const { data } = await axios.post(url, payload, {
    headers: { 'Accept': 'application/json' },
    withCredentials: true,
  })
    successMessage.value = 'Datos guardados correctamente'
    if (data?.clinic) {
      // update local reactive clinic so template reflects changes immediately
      try {
        // add a cache-buster so the browser reloads the image when path changes
        const cacheBustedUrl = data.clinic.logo_url ? `${data.clinic.logo_url}?v=${Date.now()}` : null
        clinic.value = { ...data.clinic, logo_url: cacheBustedUrl }

        // also attempt to update page props for consistency (safe)
        if (page && page.props) {
          page.props.clinic = { ...data.clinic, logo_url: cacheBustedUrl }
        }

        // dispatch a browser event so other components (ApplicationLogo) can react
        window.dispatchEvent(new CustomEvent('clinic-updated', { detail: clinic.value }))

        // update favicon if present (with cache-buster)
        if (cacheBustedUrl) {
          const link = document.querySelector("link[rel*='icon']") || document.createElement('link')
          link.type = 'image/png'
          link.rel = 'icon'
          link.href = cacheBustedUrl
          document.getElementsByTagName('head')[0].appendChild(link)
        }

        // clear upload state so the preview/input resets after successful save
        logoFile.value = null
        logoPreview.value = null
        removeLogoFlag.value = false
        if (fileInput.value && typeof fileInput.value.value !== 'undefined') {
          fileInput.value.value = ''
        }
      } catch (e) {
        // swallow errors from DOM hacks
      }
    }
  } catch (err) {
    if (err.response) {
      if (err.response.status === 422) {
        errors.value = err.response.data.errors || err.response.data || {}
      } else if (err.response.status === 419) {
        errors.value = { general: ['Sesión expirada o token CSRF inválido. Recarga la página e inténtalo de nuevo.'] }
      } else {
        errors.value = { general: [`Error ${err.response.status}. Intenta nuevamente.`] }
      }
    } else {
      errors.value = { general: ['Error de red. Verifica tu conexión.'] }
    }
  } finally {
    processing.value = false
    setTimeout(() => (successMessage.value = ''), 3500)
  }
}

const reset = () => {
  Object.assign(form, {
    name: clinic.value.name || '',
    address: clinic.value.address || '',
    phone: clinic.value.phone || '',
    email: clinic.value.email || '',
    tax_id: clinic.value.tax_id || '',
    footer_notes: clinic.value.footer_notes || '',
  })
}

// Logo upload state and helpers
const logoFile = ref(null)
const logoPreview = ref(null)
const removeLogoFlag = ref(false)
const fileInput = ref(null)

const onFileChange = (e) => {
  const f = e.target.files && e.target.files[0]
  if (!f) return
  logoFile.value = f
  removeLogoFlag.value = false
  logoPreview.value = URL.createObjectURL(f)
}

const chooseFile = () => {
  fileInput.value && fileInput.value.click()
}

const removeLogo = () => {
  logoFile.value = null
  logoPreview.value = null
  removeLogoFlag.value = true
}

onMounted(() => {
  // populate clinic from Inertia page props (may be populated after module init)
  clinic.value = page?.props?.clinic || {};
  // initialize form from clinic data
  Object.assign(form, {
    name: clinic.value.name || '',
    address: clinic.value.address || '',
    phone: clinic.value.phone || '',
    email: clinic.value.email || '',
    tax_id: clinic.value.tax_id || '',
    footer_notes: clinic.value.footer_notes || '',
  })

  // debug logging removed to keep console clean

  // Verificar si los datos de clinic están disponibles
  if (!clinic.value || Object.keys(clinic.value).length === 0) {
    console.warn('No se recibieron datos del consultorio desde el backend.');
  } else {
    console.info('Datos del consultorio cargados correctamente:', clinic.value);
  }
})

// Watch for changes in clinic.value and update form accordingly
// Watch page props clinic (more robust) and update local clinic + form
watch(
  () => page?.props?.clinic,
  (newClinic) => {
    clinic.value = newClinic || {}
    Object.assign(form, {
      name: clinic.value.name || '',
      address: clinic.value.address || '',
      phone: clinic.value.phone || '',
      email: clinic.value.email || '',
      tax_id: clinic.value.tax_id || '',
      footer_notes: clinic.value.footer_notes || '',
    })
  },
  { immediate: true }
)
</script>
