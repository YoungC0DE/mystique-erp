<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ModuleField, RecordValue } from '@/types'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { controlClass, formFieldClass } from '@/lib/inputStyles'

const props = defineProps<{
  field: ModuleField
  modelValue: RecordValue
  error?: string
}>()
const emit = defineEmits<{ (e: 'update:modelValue', value: RecordValue): void }>()

const { t } = useI18n()

const value = computed({
  get: () => props.modelValue,
  set: (v: RecordValue) => emit('update:modelValue', v),
})

const multiValue = computed<string[]>({
  get: () => (Array.isArray(props.modelValue) ? (props.modelValue as string[]) : []),
  set: (v) => emit('update:modelValue', v),
})

function emitValue(v: RecordValue): void {
  emit('update:modelValue', v)
}

function onTextUpdate(raw: string | number | undefined): void {
  emitValue(raw === undefined ? '' : String(raw))
}

function onNumberUpdate(raw: string | number | undefined): void {
  if (raw === undefined || raw === '') {
    emitValue(null)
    return
  }
  emitValue(Number(raw))
}

function toggleMultiOption(opt: string, checked: boolean): void {
  const current = multiValue.value
  if (checked) {
    if (!current.includes(opt)) multiValue.value = [...current, opt]
  } else {
    multiValue.value = current.filter((o) => o !== opt)
  }
}
</script>

<template>
  <div :class="formFieldClass">
    <Label v-if="field.type !== 'boolean'">
      {{ field.label }}
      <span v-if="field.required" class="text-danger">*</span>
    </Label>

    <Input
      v-if="['texto', 'email', 'telefone'].includes(field.type)"
      :type="field.type === 'email' ? 'email' : field.type === 'telefone' ? 'tel' : 'text'"
      :model-value="(value as string) ?? ''"
      @update:model-value="onTextUpdate"
    />

    <Textarea
      v-else-if="field.type === 'textarea'"
      :model-value="(value as string) ?? ''"
      @update:model-value="onTextUpdate"
    />

    <Input
      v-else-if="field.type === 'numero' || field.type === 'decimal'"
      type="number"
      :step="field.type === 'decimal' ? 'any' : '1'"
      :model-value="value === null || value === undefined ? '' : String(value)"
      @update:model-value="onNumberUpdate"
    />

    <Input
      v-else-if="field.type === 'data'"
      type="date"
      :model-value="(value as string) ?? ''"
      @update:model-value="onTextUpdate"
    />

    <Input
      v-else-if="field.type === 'datetime'"
      type="datetime-local"
      :model-value="(value as string) ?? ''"
      @update:model-value="onTextUpdate"
    />

    <label v-else-if="field.type === 'boolean'" class="flex cursor-pointer items-center gap-2 text-sm">
      <Checkbox
        :checked="!!value"
        @update:checked="(v: boolean | 'indeterminate') => emitValue(v === true)"
      />
      <span>{{ field.label }}</span>
    </label>

    <select
      v-else-if="field.type === 'select'"
      :value="(value as string) ?? ''"
      :class="controlClass"
      @change="emitValue(($event.target as HTMLSelectElement).value)"
    >
      <option value="">{{ t('dynamicForm.selectPlaceholder') }}</option>
      <option v-for="opt in field.options ?? []" :key="opt" :value="opt">{{ opt }}</option>
    </select>

    <div
      v-else-if="field.type === 'multiselect'"
      class="flex max-h-40 flex-col gap-2 overflow-y-auto rounded-lg border border-border/80 bg-muted/30 p-3.5"
    >
      <label
        v-for="opt in field.options ?? []"
        :key="opt"
        class="flex cursor-pointer items-center gap-2 text-sm"
      >
        <Checkbox
          :checked="multiValue.includes(opt)"
          @update:checked="(v: boolean | 'indeterminate') => toggleMultiOption(opt, v === true)"
        />
        <span>{{ opt }}</span>
      </label>
    </div>

    <span v-if="error" class="text-xs text-danger">{{ error }}</span>
  </div>
</template>
