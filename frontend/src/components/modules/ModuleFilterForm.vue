<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ModuleField } from '@/types'
import {
  createEmptyFilter,
  operatorNeedsListValue,
  operatorNeedsSecondValue,
  operatorsForFieldType,
  type FieldFilter,
  type FilterOperator,
} from '@/lib/filters'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Icon } from '@/components/ui/icon'
import { Checkbox } from '@/components/ui/checkbox'
import { controlClass } from '@/lib/inputStyles'

const props = defineProps<{
  fields: ModuleField[]
  modelValue: FieldFilter[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: FieldFilter[]): void
}>()

const { t } = useI18n()

const filters = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const visibleFields = computed(() =>
  [...props.fields].filter((f) => f.visible).sort((a, b) => a.order - b.order),
)

function operatorLabel(op: FilterOperator): string {
  return t(`filters.operators.${op}`)
}

function addFilter(): void {
  const first = visibleFields.value[0]
  filters.value = [...filters.value, createEmptyFilter(first?.key ?? '')]
}

function removeFilter(id: string): void {
  filters.value = filters.value.filter((f) => f.id !== id)
}

function onFieldChange(filter: FieldFilter): void {
  const field = visibleFields.value.find((f) => f.key === filter.field)
  if (!field) return
  const allowed = operatorsForFieldType(field.type)
  if (!allowed.includes(filter.operator)) {
    filter.operator = allowed[0]
    filter.value = field.type === 'boolean' ? true : ''
    filter.value_to = null
  }
}

function fieldType(key: string): ModuleField['type'] {
  return visibleFields.value.find((f) => f.key === key)?.type ?? 'texto'
}
</script>

<template>
  <div>
    <div v-if="filters.length === 0" class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
      {{ t('filters.empty') }}
    </div>

    <div v-for="(filter, index) in filters" :key="filter.id" class="mb-4 rounded-md border p-3">
      <div class="mb-2 flex items-center justify-between">
        <Badge variant="muted">{{ t('filters.rule', { n: index + 1 }) }}</Badge>
        <Button variant="ghost" size="icon" class="h-8 w-8" @click="removeFilter(filter.id)">
          <Icon name="trash-2" :size="16" />
        </Button>
      </div>

      <div class="space-y-2">
        <div class="space-y-1">
          <Label>{{ t('filters.field') }}</Label>
          <select
            v-model="filter.field"
            :class="controlClass"
            @change="onFieldChange(filter)"
          >
            <option v-for="f in visibleFields" :key="f.id" :value="f.key">
              {{ f.label }}
            </option>
          </select>
        </div>

        <div class="space-y-1">
          <Label>{{ t('filters.operator') }}</Label>
          <select v-model="filter.operator" :class="controlClass">
            <option
              v-for="op in operatorsForFieldType(fieldType(filter.field))"
              :key="op"
              :value="op"
            >
              {{ operatorLabel(op) }}
            </option>
          </select>
        </div>

        <div v-if="fieldType(filter.field) === 'boolean'" class="space-y-1">
          <Label>{{ t('filters.value') }}</Label>
          <label class="flex items-center gap-2 text-sm">
            <Checkbox
              :checked="filter.value === true || filter.value === 'true' || filter.value === '1'"
              @update:checked="(v: boolean | 'indeterminate') => { filter.value = v === true }"
            />
            <span>{{ t('common.yes') }}</span>
          </label>
        </div>

        <div v-else-if="operatorNeedsListValue(filter.operator)" class="space-y-1">
          <Label>{{ t('filters.listValue') }}</Label>
          <Input
            :model-value="String(filter.value ?? '')"
            :placeholder="t('filters.listPlaceholder')"
            @update:model-value="filter.value = String($event)"
          />
        </div>

        <template v-else>
          <div class="space-y-1">
            <Label>{{ t('filters.value') }}</Label>
            <Input
              :model-value="String(filter.value ?? '')"
              @update:model-value="filter.value = String($event)"
            />
          </div>
          <div v-if="operatorNeedsSecondValue(filter.operator)" class="space-y-1">
            <Label>{{ t('filters.valueTo') }}</Label>
            <Input
              :model-value="String(filter.value_to ?? '')"
              @update:model-value="filter.value_to = String($event)"
            />
          </div>
        </template>
      </div>
    </div>

    <Button variant="secondary" class="w-full gap-1.5" :disabled="visibleFields.length === 0" @click="addFilter">
      <Icon name="plus" :size="18" />
      {{ t('filters.addRule') }}
    </Button>
  </div>
</template>
