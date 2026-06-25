<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { CheckboxIndicator, CheckboxRoot, type CheckboxRootEmits, type CheckboxRootProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<
  CheckboxRootProps & {
    class?: HTMLAttributes['class']
    /** Alias shadcn/vue — mapeado para modelValue do reka-ui */
    checked?: boolean | 'indeterminate' | null
  }
>()

const emits = defineEmits<
  CheckboxRootEmits & {
    'update:checked': [value: boolean | 'indeterminate']
  }
>()

const delegatedProps = computed(() => {
  const { class: _, checked, modelValue, ...rest } = props
  return rest
})

const boundValue = computed(() => {
  if (props.modelValue !== undefined && props.modelValue !== null) {
    return props.modelValue
  }
  if (props.checked !== undefined && props.checked !== null) {
    return props.checked
  }
  return undefined
})

function onUpdate(value: boolean | 'indeterminate'): void {
  emits('update:modelValue', value)
  emits('update:checked', value)
}
</script>

<template>
  <CheckboxRoot
    v-bind="delegatedProps"
    :model-value="boundValue"
    :class="
      cn(
        'peer h-4 w-4 shrink-0 rounded-[5px] border border-input bg-card shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground',
        props.class,
      )
    "
    @update:model-value="onUpdate"
  >
    <CheckboxIndicator class="flex items-center justify-center text-current">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="h-3 w-3">
        <path d="M20 6 9 17l-5-5" />
      </svg>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
