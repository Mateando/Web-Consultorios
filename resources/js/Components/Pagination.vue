<template>
  <nav v-if="paginator && paginator.links" class="mt-4">
    <!-- Mobile prev/next -->
    <div class="flex-1 flex justify-between sm:hidden">
      <Link
        v-if="paginator.prev_page_url"
        :href="buildUrl(paginator.prev_page_url)"
        class="relative inline-flex items-center px-4 py-2 border border-transparent bg-gray-800 text-xs font-semibold uppercase tracking-widest text-white rounded-md hover:bg-gray-700"
      >
        <slot name="prev">Anterior</slot>
      </Link>
      <Link
        v-if="paginator.next_page_url"
        :href="buildUrl(paginator.next_page_url)"
        class="ml-3 relative inline-flex items-center px-4 py-2 border border-transparent bg-gray-800 text-xs font-semibold uppercase tracking-widest text-white rounded-md hover:bg-gray-700"
      >
        <slot name="next">Siguiente</slot>
      </Link>
    </div>

    <!-- Desktop numeric -->
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
      <div>
        <span class="relative z-0 inline-flex shadow-sm rounded-md">
          <template v-for="(link, idx) in paginator.links" :key="idx">
            <Link
              v-if="link.url"
              :href="buildUrl(link.url)"
              :class="[ 'relative inline-flex items-center px-4 py-2 border text-sm font-medium', link.active ? 'bg-gray-800 text-white border-transparent' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' ]"
            >
              <span v-html="decodeHtml(link.label)"></span>
            </Link>
            <span v-else class="relative inline-flex items-center px-4 py-2 border bg-white text-gray-500 text-sm" v-html="decodeHtml(link.label)"></span>
          </template>
        </span>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { unref } from 'vue'

const props = defineProps({
  paginator: Object,
  // extraParams: object of key->value to merge into paginator links (e.g., { view: 'list' })
  extraParams: { type: Object, default: () => ({}) },
})

const buildUrl = (rawUrl) => {
  if (!rawUrl) return rawUrl
  try {
    const u = new URL(rawUrl, window.location.origin)
    // merge extra params (support refs/unref)
    const params = props.extraParams || {}
    for (const k of Object.keys(params)) {
      const v = unref(params[k])
      if (v !== null && typeof v !== 'undefined' && String(v) !== '') {
        u.searchParams.set(k, String(v))
      }
    }
    return u.toString()
  } catch (e) {
    return rawUrl
  }
}

const decodeHtml = (html) => {
  try {
    const txt = document.createElement('textarea')
    txt.innerHTML = html || ''
    return txt.value
  } catch (e) {
    return html
  }
}
</script>

<style scoped>
/* small spacing adjustments are left to consumers */
</style>
