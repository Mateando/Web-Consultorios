<template>
  <Head title="Datos del Consultorio" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configuración</h2>
          <div class="text-sm text-gray-500">Datos del Consultorio</div>
        </div>
        <div class="flex items-center gap-4">
          <Link :href="route('admin.config.generales')" class="text-sm text-blue-600 hover:underline">Generales</Link>
          <Link :href="route('admin.config.study-types')" class="text-sm text-blue-600 hover:underline">Tipos de Estudios</Link>
        </div>
      </div>
    </template>

    <div class="py-8 max-w-6xl mx-auto px-4">
      <div v-if="successMessage" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded">
        {{ successMessage }}
      </div>

      <div v-if="hasErrors" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded">
        Error al guardar. Revisa los campos marcados.
      </div>

      <form @submit.prevent="submit" class="space-y-4 max-w-2xl">
        <div>
          <label class="block text-sm font-medium">Nombre</label>
          <input v-model="form.name" type="text" class="mt-1 block w-full" />
          <div v-if="getError('name')" class="mt-1 text-sm text-red-600">{{ getError('name') }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Dirección</label>
          <input v-model="form.address" type="text" class="mt-1 block w-full" />
          <div v-if="getError('address')" class="mt-1 text-sm text-red-600">{{ getError('address') }}</div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium">Teléfono</label>
            <input v-model="form.phone" type="text" class="mt-1 block w-full" />
            <div v-if="getError('phone')" class="mt-1 text-sm text-red-600">{{ getError('phone') }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium">Email</label>
            <input v-model="form.email" type="email" class="mt-1 block w-full" />
            <div v-if="getError('email')" class="mt-1 text-sm text-red-600">{{ getError('email') }}</div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium">CUIT / NIF</label>
          <input v-model="form.tax_id" type="text" class="mt-1 block w-full" />
          <div v-if="getError('tax_id')" class="mt-1 text-sm text-red-600">{{ getError('tax_id') }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Notas pie</label>
          <textarea v-model="form.footer_notes" rows="3" class="mt-1 block w-full"></textarea>
          <div v-if="getError('footer_notes')" class="mt-1 text-sm text-red-600">{{ getError('footer_notes') }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Logo (se usará en encabezados y favicon)</label>
          <div class="mt-2 flex items-center gap-4">
            <div v-if="logoPreview || clinic.logo_url" class="h-20 w-40 flex items-center justify-center border bg-white">
              <img v-if="logoPreview" :src="logoPreview" alt="Preview" class="h-full object-contain" />
              <img v-else-if="clinic.logo_url" :src="clinic.logo_url" alt="Logo actual" class="h-full object-contain" />
            </div>
            <div class="flex flex-col gap-2">
              <input ref="fileInput" @change="onFileChange" type="file" accept="image/*" />
              <div class="flex gap-2">
                <SecondaryButton type="button" @click="chooseFile">Subir</SecondaryButton>
                <SecondaryButton type="button" class="bg-red-50 text-red-600" @click="removeLogo">Eliminar</SecondaryButton>
              </div>
            </div>
          </div>
          <div v-if="getError('logo')" class="mt-1 text-sm text-red-600">{{ getError('logo') }}</div>
        </div>

        <div class="flex gap-2">
          <PrimaryButton type="submit" :disabled="processing">{{ processing ? 'Guardando...' : 'Guardar' }}</PrimaryButton>
          <SecondaryButton type="button" @click="reset">Restablecer</SecondaryButton>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
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
  const { data } = await axios.post(url, payload)
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
    if (err.response && err.response.status === 422) {
      errors.value = err.response.data.errors || err.response.data || {}
    } else {
      errors.value = { general: ['Error al guardar. Intenta nuevamente.'] }
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
