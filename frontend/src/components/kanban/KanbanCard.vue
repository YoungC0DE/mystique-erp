<script setup lang="ts">

import { computed } from 'vue'

import { useI18n } from 'vue-i18n'

import type { ModuleField, ModuleRecord, RecordValue } from '@/types'

import { cn } from '@/lib/utils'



const props = defineProps<{

  record: ModuleRecord

  fields: ModuleField[]

  draggable?: boolean

  readOnly?: boolean

}>()

const emit = defineEmits<{

  (e: 'open', record: ModuleRecord): void

  (e: 'dragstart', record: ModuleRecord): void

  (e: 'dragend'): void

}>()



const { t } = useI18n()



const MAX_LIST_FIELDS = 6



const listFields = computed(() =>

  [...props.fields]

    .filter((f) => f.show_in_list && f.visible)

    .sort((a, b) => a.order - b.order)

    .slice(0, MAX_LIST_FIELDS),

)



const highlightedField = computed(() => listFields.value.find((f) => f.highlighted) ?? listFields.value[0])



const secondaryFields = computed(() =>

  listFields.value.filter((f) => f.id !== highlightedField.value?.id),

)



function display(value: RecordValue): string {

  if (value === null || value === undefined || value === '') return '—'

  if (Array.isArray(value)) return value.join(', ')

  if (typeof value === 'boolean') return value ? t('common.yes') : t('common.no')

  return String(value)

}



const shortId = computed(() => String(props.record.id).slice(0, 8))



function onClick(): void {

  emit('open', props.record)

}

</script>



<template>

  <div

    :class="

      cn(

        'cursor-pointer rounded-lg border border-border/70 bg-card p-3.5 shadow-sm',

        'transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-card',

        'active:scale-[0.99]',

      )

    "

    :draggable="draggable"

    @dragstart="emit('dragstart', record)"

    @dragend="emit('dragend')"

    @click="onClick"

  >

    <strong v-if="highlightedField" class="mb-2 block text-sm font-semibold text-primary">

      {{ display(record.values[highlightedField.key]) }}

    </strong>

    <strong v-else class="mb-2 block text-sm font-semibold">#{{ shortId }}</strong>



    <div

      v-for="f in secondaryFields"

      :key="f.id"

      :class="[

        'mt-1 flex justify-between gap-2 text-[12.5px]',

        f.highlighted ? 'font-medium text-foreground' : '',

      ]"

    >

      <span class="text-muted-foreground">{{ f.label }}</span>

      <span class="text-right font-medium">{{ display(record.values[f.key]) }}</span>

    </div>



    <div class="mt-2.5 text-[11px] font-medium text-muted-foreground/80">

      #{{ shortId }}

    </div>

  </div>

</template>

