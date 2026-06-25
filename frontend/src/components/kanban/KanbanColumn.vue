<script setup lang="ts">
import { ref } from 'vue';

import { useI18n } from 'vue-i18n';

import type { KanbanColumnData, ModuleField, ModuleRecord } from '@/types';

import { kanbanStatusAccent } from '@/lib/kanban';

import { Badge } from '@/components/ui/badge';

import { Button } from '@/components/ui/button';

import { Icon } from '@/components/ui/icon';

import KanbanCard from './KanbanCard.vue';

import { cn } from '@/lib/utils';
const props = defineProps<{
  column: KanbanColumnData;

  fields: ModuleField[];

  canMove: boolean;

  readOnly?: boolean;
}>();

const emit = defineEmits<{
  (e: 'open', record: ModuleRecord): void;

  (e: 'drop', payload: { status: string }): void;

  (e: 'dragstart', record: ModuleRecord): void;

  (e: 'dragend'): void;

  (e: 'page', payload: { status: string; page: number }): void;
}>();
const { t } = useI18n();

const isOver = ref(false);
function columnAccent(): string {
  return props.column.color ?? kanbanStatusAccent(props.column.key);
}
function onDrop(): void {
  isOver.value = false;

  if (props.canMove) emit('drop', { status: props.column.key });
}
</script>
<template>
  <div
    :class="
      cn(
        'flex max-h-full w-[292px] min-w-[292px] shrink-0 flex-col rounded-xl border border-border/80 bg-card/60 shadow-card backdrop-blur-sm',

        isOver && canMove && 'border-primary/50 ring-2 ring-primary/15',
      )
    "
    @dragover.prevent="isOver = true"
    @dragleave="isOver = false"
    @drop.prevent="onDrop"
  >
    <div class="flex items-center gap-2.5 border-b border-border/60 px-4 py-3.5">
      <span class="h-2.5 w-2.5 shrink-0 rounded-full ring-2 ring-background" :style="{ background: columnAccent() }" />

      <strong class="text-sm font-semibold tracking-tight">{{ column.label }}</strong>

      <Badge variant="muted" class="ml-auto tabular-nums">{{ column.meta.total }}</Badge>
    </div>
    <div class="flex flex-1 flex-col gap-2.5 overflow-y-auto p-3">
      <KanbanCard
        v-for="record in column.records"
        :key="record.id"
        :record="record"
        :fields="fields"
        :draggable="canMove"
        :read-only="readOnly"
        @open="emit('open', $event)"
        @dragstart="emit('dragstart', $event)"
        @dragend="emit('dragend')"
      />

      <p v-if="column.records.length === 0" class="py-6 text-center text-[13px] text-muted-foreground">
        {{ t('board.emptyColumn') }}
      </p>
    </div>
    <div
      v-if="column.meta.last_page > 1"
      class="flex items-center justify-center gap-2 border-t border-border/60 p-2.5 text-xs"
    >
      <Button
        variant="ghost"
        size="sm"
        :disabled="column.meta.current_page <= 1"
        @click="emit('page', { status: column.key, page: column.meta.current_page - 1 })"
      >
        <Icon name="chevron-left" :size="18" />
      </Button>

      <span class="min-w-[48px] text-center font-medium text-muted-foreground">
        {{ column.meta.current_page }}/{{ column.meta.last_page }}
      </span>

      <Button
        variant="ghost"
        size="sm"
        :disabled="column.meta.current_page >= column.meta.last_page"
        @click="emit('page', { status: column.key, page: column.meta.current_page + 1 })"
      >
        <Icon name="chevron-right" :size="18" />
      </Button>
    </div>
  </div>
</template>
