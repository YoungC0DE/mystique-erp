<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ModuleField, RecordValue } from '@/types'
import DynamicField from './DynamicField.vue'

const props = defineProps<{
  fields: ModuleField[]
  modelValue: Record<string, RecordValue>
}>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: Record<string, RecordValue>): void
}>()

const { t } = useI18n()
const errors = reactive<Record<string, string>>({})

const orderedFields = computed(() =>
  [...props.fields].filter((f) => f.visible).sort((a, b) => a.order - b.order),
)

const local = ref<Record<string, RecordValue>>({ ...props.modelValue })

watch(
  () => props.modelValue,
  (v) => {
    local.value = { ...v }
  },
)

function setValue(key: string, value: RecordValue): void {
  local.value = { ...local.value, [key]: value }
  delete errors[key]
  emit('update:modelValue', local.value)
}

function isEmpty(value: RecordValue): boolean {
  if (value === null || value === undefined || value === '') return true
  if (Array.isArray(value) && value.length === 0) return true
  return false
}

/** Valida os campos obrigatórios. Retorna true se válido. */
function validate(): boolean {
  Object.keys(errors).forEach((k) => delete errors[k])
  let valid = true
  for (const field of orderedFields.value) {
    if (field.required && field.type !== 'boolean' && isEmpty(local.value[field.key])) {
      errors[field.key] = t('dynamicForm.requiredError')
      valid = false
    }
  }
  return valid
}

defineExpose({ validate })
</script>

<template>
  <div class="space-y-3.5">
    <p v-if="orderedFields.length === 0" class="text-sm text-muted-foreground">
      {{ t('dynamicForm.noFields') }}
    </p>
    <DynamicField
      v-for="field in orderedFields"
      :key="field.id"
      :field="field"
      :model-value="local[field.key] ?? null"
      :error="errors[field.key]"
      @update:model-value="(v) => setValue(field.key, v)"
    />
  </div>
</template>
