<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { controlClass } from '@/lib/inputStyles';

const model = defineModel<string | number>();

defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <input v-model="model" :class="cn(controlClass, $props.class)" />
</template>
