<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { DetailLayout, ModuleField, ModuleKanbanStatus, ModuleRecord, RecordValue } from '@/types';
import { normalizeDetailLayout, rowGridClass } from '@/lib/detailLayout';
import { recordsService } from '@/services/records.service';
import { apiErrorMessage } from '@/services/http';
import { useToast } from '@/composables/useToast';
import BaseModal from '@/components/ui/BaseModal.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { controlClass } from '@/lib/inputStyles';

const props = defineProps<{
  record: ModuleRecord;
  fields: ModuleField[];
  detailLayout?: DetailLayout | null;
  statuses?: ModuleKanbanStatus[];
  moduleId: string;
  canMoveStatus?: boolean;
  isIntegrated?: boolean;
  title?: string;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'status-changed', payload: { recordId: string; from: string; to: string }): void;
}>();

const { t } = useI18n();
const toast = useToast();

const currentStatus = ref(props.record.status);
const noteBody = ref('');
const statusSaving = ref(false);

const layoutRows = computed(() => {
  const rows = normalizeDetailLayout(props.detailLayout);
  if (rows.length) return rows;

  return props.fields
    .filter((f) => f.show_in_detail && f.visible)
    .sort((a, b) => a.order - b.order)
    .map((f) => ({ field_keys: [f.key] }));
});

function fieldByKey(key: string): ModuleField | undefined {
  return props.fields.find((f) => f.key === key);
}

function display(value: RecordValue): string {
  if (value === null || value === undefined || value === '') return '—';
  if (Array.isArray(value)) return value.join(', ');
  if (typeof value === 'boolean') return value ? t('common.yes') : t('common.no');
  return String(value);
}

async function loadNote(): Promise<void> {
  try {
    const note = await recordsService.getNote(props.moduleId, props.record.id);
    noteBody.value = note.body ?? '';
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function saveNote(): Promise<void> {
  try {
    await recordsService.saveNote(props.moduleId, props.record.id, noteBody.value || null);
    toast.success(t('recordDetail.noteSaved'));
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function onStatusChange(next: string): Promise<void> {
  if (next === props.record.status || !props.canMoveStatus) return;
  const previous = props.record.status;
  currentStatus.value = next;
  statusSaving.value = true;
  try {
    if (props.isIntegrated) {
      await recordsService.moveIntegrated(props.moduleId, props.record.id, next);
    } else {
      await recordsService.move(props.record.id, next);
    }
    emit('status-changed', { recordId: props.record.id, from: previous, to: next });
    toast.success(t('recordDetail.stageUpdated'));
  } catch (e) {
    currentStatus.value = previous;
    toast.error(apiErrorMessage(e));
  } finally {
    statusSaving.value = false;
  }
}

watch(
  () => props.record.id,
  () => {
    currentStatus.value = props.record.status;
    loadNote();
  },
  { immediate: true },
);

watch(
  () => props.record.status,
  (value) => {
    currentStatus.value = value;
  },
);
</script>

<template>
  <BaseModal large :title="title ?? t('recordDetail.title')" @close="emit('close')">
    <div class="mb-5 space-y-1.5">
      <Label>{{ t('recordDetail.stage') }}</Label>
      <select
        v-model="currentStatus"
        :class="controlClass"
        :disabled="!canMoveStatus || statusSaving || !statuses?.length"
        @change="onStatusChange(currentStatus)"
      >
        <option v-for="status in statuses ?? []" :key="status.slug" :value="status.slug">
          {{ status.label }}
        </option>
      </select>
      <p class="text-xs text-muted-foreground">#{{ String(record.id).slice(0, 8) }}</p>
    </div>

    <div class="space-y-4">
      <div
        v-for="(row, rowIndex) in layoutRows"
        :key="rowIndex"
        :class="['grid gap-3', rowGridClass(row.field_keys.length)]"
      >
        <div v-for="key in row.field_keys" :key="key" class="rounded-md border bg-muted/30 px-3 py-2.5">
          <div class="text-xs text-muted-foreground">{{ fieldByKey(key)?.label ?? key }}</div>
          <div class="mt-0.5 text-sm font-medium break-words">{{ display(record.values[key]) }}</div>
        </div>
      </div>

      <p v-if="layoutRows.length === 0" class="text-sm text-muted-foreground">
        {{ t('recordDetail.noFields') }}
      </p>
    </div>

    <div class="mt-6 border-t pt-5">
      <Label class="mb-1.5 block">{{ t('recordDetail.internalNote') }}</Label>
      <p class="mb-2 text-xs text-muted-foreground">{{ t('recordDetail.internalNoteHint') }}</p>
      <Textarea v-model="noteBody" :placeholder="t('recordDetail.internalNotePlaceholder')" rows="4" />
      <div class="mt-2 flex justify-end">
        <Button size="sm" @click="saveNote">{{ t('recordDetail.saveNote') }}</Button>
      </div>
    </div>

    <template #footer>
      <Button variant="secondary" @click="emit('close')">{{ t('common.cancel') }}</Button>
    </template>
  </BaseModal>
</template>
