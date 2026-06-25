<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { DetailLayoutRow, ModuleField } from '@/types'
import {
  MAX_FIELDS_PER_DETAIL_ROW,
  allKeysInRows,
  rowGridClass,
} from '@/lib/detailLayout'
import { Button } from '@/components/ui/button'
import { Icon } from '@/components/ui/icon'

const props = defineProps<{
  fields: ModuleField[]
  rows: DetailLayoutRow[]
}>()

const emit = defineEmits<{
  (e: 'update:rows', value: DetailLayoutRow[]): void
}>()

const { t } = useI18n()

const localRows = ref<DetailLayoutRow[]>([])
const dragFieldKey = ref<string | null>(null)
const dragFromRow = ref<number | null>(null)
const dragFromCol = ref<number | null>(null)

watch(
  () => props.rows,
  (value) => {
    localRows.value = value.map((r) => ({ field_keys: [...r.field_keys] }))
  },
  { immediate: true, deep: true },
)

const placedKeys = computed(() => new Set(allKeysInRows(localRows.value)))

const paletteFields = computed(() =>
  props.fields
    .filter((f) => f.visible && !placedKeys.value.has(f.key))
    .sort((a, b) => a.label.localeCompare(b.label)),
)

function fieldByKey(key: string): ModuleField | undefined {
  return props.fields.find((f) => f.key === key)
}

function commit(): void {
  emit(
    'update:rows',
    localRows.value.map((r) => ({ field_keys: [...r.field_keys] })),
  )
}

function addRow(): void {
  localRows.value.push({ field_keys: [] })
  commit()
}

function removeRow(rowIndex: number): void {
  localRows.value.splice(rowIndex, 1)
  commit()
}

function removeField(rowIndex: number, colIndex: number): void {
  localRows.value[rowIndex].field_keys.splice(colIndex, 1)
  if (localRows.value[rowIndex].field_keys.length === 0) {
    localRows.value.splice(rowIndex, 1)
  }
  commit()
}

function onPaletteDragStart(key: string): void {
  dragFieldKey.value = key
  dragFromRow.value = null
  dragFromCol.value = null
}

function onFieldDragStart(rowIndex: number, colIndex: number, key: string): void {
  dragFieldKey.value = key
  dragFromRow.value = rowIndex
  dragFromCol.value = colIndex
}

function onDragEnd(): void {
  dragFieldKey.value = null
  dragFromRow.value = null
  dragFromCol.value = null
}

function insertIntoRow(rowIndex: number, colIndex?: number): void {
  const key = dragFieldKey.value
  if (!key) return

  if (dragFromRow.value !== null && dragFromCol.value !== null) {
    localRows.value[dragFromRow.value].field_keys.splice(dragFromCol.value, 1)
    if (localRows.value[dragFromRow.value].field_keys.length === 0) {
      localRows.value.splice(dragFromRow.value, 1)
      if (dragFromRow.value < rowIndex) rowIndex--
    }
  }

  const row = localRows.value[rowIndex]
  if (!row || row.field_keys.length >= MAX_FIELDS_PER_DETAIL_ROW) return
  if (row.field_keys.includes(key)) {
    commit()
    onDragEnd()
    return
  }

  const insertAt = colIndex ?? row.field_keys.length
  row.field_keys.splice(insertAt, 0, key)
  commit()
  onDragEnd()
}

function onDropNewRow(): void {
  const key = dragFieldKey.value
  if (!key) return

  if (dragFromRow.value !== null && dragFromCol.value !== null) {
    localRows.value[dragFromRow.value].field_keys.splice(dragFromCol.value, 1)
    if (localRows.value[dragFromRow.value].field_keys.length === 0) {
      localRows.value.splice(dragFromRow.value, 1)
    }
  }

  localRows.value.push({ field_keys: [key] })
  commit()
  onDragEnd()
}
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm text-muted-foreground">{{ t('moduleConfig.detailBuilderHint') }}</p>

    <div class="grid gap-4 lg:grid-cols-[220px_1fr]">
      <div class="rounded-lg border bg-muted/20 p-3">
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
          {{ t('moduleConfig.palette') }}
        </h4>
        <ul class="space-y-1.5">
          <li
            v-for="field in paletteFields"
            :key="field.id"
            draggable="true"
            class="cursor-grab rounded border bg-card px-2 py-1.5 text-sm active:cursor-grabbing"
            @dragstart="onPaletteDragStart(field.key)"
            @dragend="onDragEnd"
          >
            {{ field.label }}
          </li>
          <li v-if="paletteFields.length === 0" class="py-4 text-center text-xs text-muted-foreground">
            {{ t('moduleConfig.paletteEmpty') }}
          </li>
        </ul>
      </div>

      <div class="space-y-3">
        <div
          v-for="(row, rowIndex) in localRows"
          :key="rowIndex"
          class="rounded-lg border border-dashed p-3"
          @dragover.prevent
          @drop.prevent="insertIntoRow(rowIndex)"
        >
          <div class="mb-2 flex items-center justify-between">
            <span class="text-xs text-muted-foreground">
              {{ t('moduleConfig.rowLabel', { n: rowIndex + 1 }) }}
              · {{ row.field_keys.length }}/{{ MAX_FIELDS_PER_DETAIL_ROW }}
            </span>
            <Button variant="ghost" size="icon" class="h-7 w-7" @click="removeRow(rowIndex)">
              <Icon name="trash-2" :size="16" />
            </Button>
          </div>
          <div :class="['grid gap-2', rowGridClass(row.field_keys.length || 1)]">
            <div
              v-for="(key, colIndex) in row.field_keys"
              :key="key"
              draggable="true"
              class="flex items-center justify-between gap-2 rounded-md border bg-card px-3 py-2 text-sm"
              @dragstart="onFieldDragStart(rowIndex, colIndex, key)"
              @dragend="onDragEnd"
              @dragover.prevent
              @drop.prevent.stop="insertIntoRow(rowIndex, colIndex)"
            >
              <span class="flex items-center gap-1.5 truncate font-medium">
                <Icon name="grip-vertical" :size="16" class="shrink-0 text-muted-foreground" />
                {{ fieldByKey(key)?.label ?? key }}
              </span>
              <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0" @click="removeField(rowIndex, colIndex)">
                <Icon name="x" :size="14" />
              </Button>
            </div>
            <div
              v-if="row.field_keys.length < MAX_FIELDS_PER_DETAIL_ROW"
              class="flex min-h-[44px] items-center justify-center rounded-md border border-dashed text-xs text-muted-foreground"
              @dragover.prevent
              @drop.prevent.stop="insertIntoRow(rowIndex)"
            >
              {{ t('moduleConfig.dropHere') }}
            </div>
          </div>
        </div>

        <div
          class="flex min-h-[72px] items-center justify-center rounded-lg border-2 border-dashed text-sm text-muted-foreground"
          @dragover.prevent
          @drop.prevent="onDropNewRow"
        >
          {{ t('moduleConfig.dropNewRow') }}
        </div>

        <Button variant="secondary" size="sm" class="gap-1" @click="addRow">
          <Icon name="plus" :size="16" />
          {{ t('moduleConfig.addRow') }}
        </Button>
      </div>
    </div>
  </div>
</template>
