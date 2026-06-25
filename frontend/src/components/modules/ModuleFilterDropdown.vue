<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ModuleField } from '@/types'
import type { FieldFilter } from '@/lib/filters'
import ModuleFilterForm from '@/components/modules/ModuleFilterForm.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Icon } from '@/components/ui/icon'

const props = defineProps<{
  fields: ModuleField[]
  modelValue: FieldFilter[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: FieldFilter[]): void
  (e: 'apply'): void
}>()

const { t } = useI18n()

const open = ref(false)
const draft = ref<FieldFilter[]>([])
const rootRef = ref<HTMLElement | null>(null)

const activeCount = ref(0)

watch(
  () => props.modelValue,
  (value) => {
    activeCount.value = value.filter((f) => f.field).length
  },
  { immediate: true, deep: true },
)

watch(open, (isOpen) => {
  if (isOpen) {
    draft.value = props.modelValue.length
      ? props.modelValue.map((f) => ({ ...f }))
      : []
  }
})

function apply(): void {
  emit('update:modelValue', draft.value.filter((f) => f.field))
  emit('apply')
  open.value = false
}

function clear(): void {
  draft.value = []
  emit('update:modelValue', [])
  emit('apply')
  open.value = false
}

function toggleOpen(): void {
  open.value = !open.value
}

function onDocumentClick(event: MouseEvent): void {
  if (!open.value || !rootRef.value) return
  if (!rootRef.value.contains(event.target as Node)) {
    open.value = false
  }
}

function onDocumentKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') open.value = false
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onDocumentKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onDocumentKeydown)
})
</script>

<template>
  <div ref="rootRef" class="relative">
    <Button variant="secondary" class="gap-1.5" @click.stop="toggleOpen">
      <Icon name="list-filter" :size="18" />
      {{ t('board.filters') }}
      <Badge v-if="activeCount" class="ml-1">{{ activeCount }}</Badge>
    </Button>

    <div
      v-if="open"
      class="absolute left-0 top-full z-[100] mt-2 w-[min(420px,calc(100vw-2rem))] rounded-xl border border-border/80 bg-card shadow-overlay"
      role="dialog"
      :aria-label="t('filters.title')"
      @click.stop
    >
      <div class="border-b px-4 py-3">
        <h2 class="text-sm font-semibold">{{ t('filters.title') }}</h2>
        <p class="mt-1 text-xs text-muted-foreground">{{ t('filters.subtitle') }}</p>
      </div>

      <div class="max-h-[min(70vh,480px)] overflow-y-auto p-4">
        <ModuleFilterForm v-model="draft" :fields="fields" />
      </div>

      <div class="flex gap-2 border-t p-3">
        <Button variant="secondary" class="flex-1" @click="clear">
          {{ t('filters.clear') }}
        </Button>
        <Button class="flex-1" @click="apply">
          {{ t('filters.apply') }}
        </Button>
      </div>
    </div>
  </div>
</template>
