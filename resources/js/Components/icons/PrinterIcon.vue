<template>
  <svg xmlns="http://www.w3.org/2000/svg" :class="iconClass" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="strokeWidth" :aria-hidden="ariaHidden" :role="role" :title="title">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v7a2 2 0 01-2 2h-2M6 18v4h12v-4" />
  </svg>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  class: { type: String, default: 'h-5 w-5' },
  strokeWidth: { type: [String, Number], default: 1.5 },
  title: { type: String, default: '' },
  ariaHidden: { type: [String, Boolean], default: 'true' },
  role: { type: String, default: 'img' }
})

const iconClass = computed(() => props.class)
</script>
