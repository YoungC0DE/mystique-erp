<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { ModuleField } from '@/types';
import { Button } from '@/components/ui/button';
import { Icon } from '@/components/ui/icon';
import { Badge } from '@/components/ui/badge';
import { fieldTypeLabel } from '@/constants/fieldTypes';

const props = defineProps<{
  fields: ModuleField[];
  maxDisplayed?: number;
}>();

const emit = defineEmits<{
  (e: 'update:fields', value: ModuleField[]): void;
}>();

const { t } = useI18n();
const maxDisplayed = computed(() => props.maxDisplayed ?? 6);

const availableFields = computed(() =>
  props.fields.filter((f) => f.visible && !f.show_in_list).sort((a, b) => a.label.localeCompare(b.label)),
);

const displayedFields = computed(() =>
  props.fields.filter((f) => f.visible && f.show_in_list).sort((a, b) => a.order - b.order),
);

const dragKey = ref<string | null>(null);

function patchField(id: string, patch: Partial<ModuleField>): void {
  emit(
    'update:fields',
    props.fields.map((f) => (f.id === id ? { ...f, ...patch } : f)),
  );
}

function addToDisplayed(field: ModuleField): void {
  if (displayedFields.value.length >= maxDisplayed.value) return;
  const nextOrder = displayedFields.value.length > 0 ? Math.max(...displayedFields.value.map((f) => f.order)) + 1 : 0;
  patchField(field.id, { show_in_list: true, order: nextOrder });
}

function removeFromDisplayed(field: ModuleField): void {
  patchField(field.id, { show_in_list: false, highlighted: false });
}

function moveDisplayed(index: number, dir: -1 | 1): void {
  const list = displayedFields.value;
  const target = index + dir;
  if (target < 0 || target >= list.length) return;
  const reordered = [...list];
  [reordered[index], reordered[target]] = [reordered[target], reordered[index]];
  const updates = new Map(reordered.map((f, i) => [f.id, i]));
  emit(
    'update:fields',
    props.fields.map((f) => (updates.has(f.id) ? { ...f, order: updates.get(f.id)! } : f)),
  );
}

function toggleHighlight(field: ModuleField): void {
  patchField(field.id, { highlighted: !field.highlighted });
}

function onDragStart(key: string): void {
  dragKey.value = key;
}

function onDragEnd(): void {
  dragKey.value = null;
}

function onDropDisplayed(index: number): void {
  if (!dragKey.value) return;
  const field = props.fields.find((f) => f.key === dragKey.value);
  if (!field) return;

  if (!field.show_in_list) {
    if (displayedFields.value.length >= maxDisplayed.value) return;
    addToDisplayed(field);
  }

  const list = [...displayedFields.value];
  const fromIndex = list.findIndex((f) => f.key === dragKey.value);
  if (fromIndex === -1) return;
  const [item] = list.splice(fromIndex, 1);
  list.splice(index, 0, item);
  const updates = new Map(list.map((f, i) => [f.id, i]));
  emit(
    'update:fields',
    props.fields.map((f) => {
      if (updates.has(f.id)) return { ...f, show_in_list: true, order: updates.get(f.id)! };
      return f;
    }),
  );
  dragKey.value = null;
}
</script>

<template>
  <div class="grid gap-4 md:grid-cols-2">
    <div class="rounded-lg border bg-muted/20 p-4">
      <h3 class="mb-1 text-sm font-semibold">{{ t('moduleConfig.availableFields') }}</h3>
      <p class="mb-3 text-xs text-muted-foreground">{{ t('moduleConfig.availableFieldsHint') }}</p>
      <ul class="space-y-2">
        <li
          v-for="field in availableFields"
          :key="field.id"
          draggable="true"
          class="flex items-center justify-between gap-2 rounded-md border bg-card px-3 py-2 text-sm"
          @dragstart="onDragStart(field.key)"
          @dragend="onDragEnd"
        >
          <div>
            <div class="font-medium">{{ field.label }}</div>
            <div class="text-xs text-muted-foreground">{{ fieldTypeLabel(field.type) }}</div>
          </div>
          <Button
            variant="ghost"
            size="icon"
            class="h-8 w-8 shrink-0"
            :disabled="displayedFields.length >= maxDisplayed"
            :title="t('moduleConfig.addToDisplay')"
            @click="addToDisplayed(field)"
          >
            <Icon name="chevron-right" :size="20" />
          </Button>
        </li>
        <li v-if="availableFields.length === 0" class="py-6 text-center text-sm text-muted-foreground">
          {{ t('moduleConfig.allFieldsDisplayed') }}
        </li>
      </ul>
    </div>

    <div class="rounded-lg border border-primary/30 bg-card p-4">
      <div class="mb-3 flex items-center justify-between gap-2">
        <div>
          <h3 class="text-sm font-semibold">{{ t('moduleConfig.displayedFields') }}</h3>
          <p class="text-xs text-muted-foreground">
            {{ t('moduleConfig.listingHint', { max: maxDisplayed, count: displayedFields.length }) }}
          </p>
        </div>
      </div>
      <ul class="space-y-2">
        <li
          v-for="(field, index) in displayedFields"
          :key="field.id"
          draggable="true"
          class="flex items-center gap-2 rounded-md border bg-muted/30 px-3 py-2 text-sm"
          @dragstart="onDragStart(field.key)"
          @dragend="onDragEnd"
          @dragover.prevent
          @drop.prevent="onDropDisplayed(index)"
        >
          <Icon name="grip-vertical" :size="18" class="shrink-0 text-muted-foreground" />
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="truncate font-medium">{{ field.label }}</span>
              <Badge v-if="field.highlighted" variant="default" class="text-[10px]">
                {{ t('moduleConfig.highlighted') }}
              </Badge>
            </div>
          </div>
          <div class="flex shrink-0 items-center gap-0.5">
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :class="field.highlighted ? 'text-primary' : ''"
              :title="t('moduleConfig.toggleHighlight')"
              @click="toggleHighlight(field)"
            >
              <Icon name="star" :size="18" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :disabled="index === 0"
              @click="moveDisplayed(index, -1)"
            >
              <Icon name="chevron-up" :size="18" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :disabled="index === displayedFields.length - 1"
              @click="moveDisplayed(index, 1)"
            >
              <Icon name="chevron-down" :size="18" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :title="t('moduleConfig.removeFromDisplay')"
              @click="removeFromDisplayed(field)"
            >
              <Icon name="chevron-left" :size="20" />
            </Button>
          </div>
        </li>
        <li
          v-if="displayedFields.length === 0"
          class="rounded-md border border-dashed py-8 text-center text-sm text-muted-foreground"
          @dragover.prevent
          @drop.prevent="onDropDisplayed(0)"
        >
          {{ t('moduleConfig.dropFieldsHere') }}
        </li>
      </ul>
    </div>
  </div>
</template>
