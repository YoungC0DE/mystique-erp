<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import type { DetailLayoutRow, Module, ModuleField } from '@/types';
import { normalizeDetailLayout, rowsFromFields, syncFieldsFromDetailRows } from '@/lib/detailLayout';
import { modulesService, type FieldPayload, type LayoutFieldPayload } from '@/services/modules.service';
import { apiErrorMessage } from '@/services/http';
import { useToast } from '@/composables/useToast';
import { FIELD_TYPES, TYPES_WITH_OPTIONS } from '@/constants/fieldTypes';
import FieldListingTransfer from '@/components/modules/FieldListingTransfer.vue';
import DetailLayoutBuilder from '@/components/modules/DetailLayoutBuilder.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Icon } from '@/components/ui/icon';
import { Textarea } from '@/components/ui/textarea';
import { controlClass } from '@/lib/inputStyles';

const props = defineProps<{ slug: string }>();
const { t } = useI18n();
const toast = useToast();
const router = useRouter();

const MAX_LIST_FIELDS = 6;

const module = ref<Module | null>(null);
const fields = ref<ModuleField[]>([]);
const detailRows = ref<DetailLayoutRow[]>([]);
const activeTab = ref<'detail' | 'listing'>('listing');
const isIntegrated = computed(() => module.value?.is_integrated ?? false);

const showModal = ref(false);
const editing = ref<ModuleField | null>(null);
const form = reactive<FieldPayload & { optionsText: string }>({
  label: '',
  key: '',
  type: 'texto',
  required: false,
  default_value: '',
  order: 0,
  optionsText: '',
});

const listFieldCount = computed(() => fields.value.filter((f) => f.show_in_list && f.visible).length);

async function load(): Promise<void> {
  try {
    const list = await modulesService.list();
    const found = list.data.find((m) => m.slug === props.slug);
    if (!found) {
      toast.error(t('moduleConfig.notFound'));
      router.push({ name: 'modules' });
      return;
    }
    module.value = await modulesService.get(found.id);
    await reloadFields();
    const normalized = normalizeDetailLayout(module.value.detail_layout);
    detailRows.value = normalized.length ? normalized : rowsFromFields(fields.value);
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function reloadFields(): Promise<void> {
  if (!module.value) return;
  fields.value = (await modulesService.listFields(module.value.id)).sort((a, b) => a.order - b.order);
}

function openCreate(): void {
  editing.value = null;
  Object.assign(form, {
    label: '',
    key: '',
    type: 'texto',
    required: false,
    default_value: '',
    order: fields.value.length,
    optionsText: '',
  });
  showModal.value = true;
}

async function saveField(): Promise<void> {
  if (!module.value) return;
  try {
    const payload: FieldPayload = {
      label: form.label,
      type: form.type,
      required: form.required,
      default_value: form.default_value || null,
      order: form.order,
    };
    if (!editing.value && form.key) payload.key = form.key;
    if (TYPES_WITH_OPTIONS.includes(form.type as ModuleField['type'])) {
      payload.options = form.optionsText
        .split('\n')
        .map((o) => o.trim())
        .filter(Boolean);
    }

    if (editing.value) {
      await modulesService.updateField(editing.value.id, payload);
      toast.success(t('moduleConfig.updated'));
    } else {
      await modulesService.createField(module.value.id, payload);
      toast.success(t('moduleConfig.created'));
    }
    showModal.value = false;
    await reloadFields();
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function saveLayout(): Promise<void> {
  if (!module.value) return;
  if (listFieldCount.value > MAX_LIST_FIELDS) {
    toast.error(t('moduleConfig.maxListFields', { max: MAX_LIST_FIELDS }));
    return;
  }

  try {
    const syncedFields = syncFieldsFromDetailRows(fields.value, detailRows.value);
    fields.value = syncedFields;

    const payload: LayoutFieldPayload[] = syncedFields.map((f) => ({
      id: f.id,
      order: f.order,
      show_in_card: f.show_in_list,
      show_in_list: f.show_in_list,
      show_in_detail: f.show_in_detail,
      highlighted: f.highlighted,
      visible: f.visible,
    }));

    await modulesService.updateLayout(module.value.id, {
      fields: payload,
      detail_layout: {
        rows: detailRows.value.filter((r) => r.field_keys.length > 0),
      },
    });
    toast.success(t('moduleConfig.layoutSaved'));
    await load();
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

onMounted(load);
</script>

<template>
  <PageContainer>
    <PageHeader
      :title="t('moduleConfig.title', { name: module?.name ?? '...' })"
      :subtitle="isIntegrated ? t('moduleConfig.subtitle') : t('moduleConfig.legacySubtitle')"
    >
      <template #actions>
        <Button variant="secondary" class="gap-1.5" @click="router.push({ name: 'module', params: { slug } })">
          <Icon name="arrow-left" :size="18" />
          {{ t('moduleConfig.backToBoard') }}
        </Button>
        <Button v-if="!isIntegrated" class="gap-1.5" @click="openCreate">
          <Icon name="plus" :size="18" />
          {{ t('moduleConfig.newField') }}
        </Button>
      </template>
    </PageHeader>

    <template v-if="module">
      <div class="mb-4 flex gap-2 border-b">
        <button
          type="button"
          :class="[
            'border-b-2 px-4 py-2 text-sm font-medium transition-colors',
            activeTab === 'listing'
              ? 'border-primary text-primary'
              : 'border-transparent text-muted-foreground hover:text-foreground',
          ]"
          @click="activeTab = 'listing'"
        >
          {{ t('moduleConfig.tabListing') }}
        </button>
        <button
          type="button"
          :class="[
            'border-b-2 px-4 py-2 text-sm font-medium transition-colors',
            activeTab === 'detail'
              ? 'border-primary text-primary'
              : 'border-transparent text-muted-foreground hover:text-foreground',
          ]"
          @click="activeTab = 'detail'"
        >
          {{ t('moduleConfig.tabDetail') }}
        </button>
      </div>

      <FieldListingTransfer v-if="activeTab === 'listing'" v-model:fields="fields" :max-displayed="MAX_LIST_FIELDS" />

      <DetailLayoutBuilder v-else :fields="fields" :rows="detailRows" @update:rows="detailRows = $event" />

      <div class="mt-6 flex justify-end">
        <Button :disabled="fields.length === 0" @click="saveLayout">
          {{ t('moduleConfig.saveLayout') }}
        </Button>
      </div>
    </template>

    <BaseModal
      v-if="showModal && !isIntegrated"
      :title="editing ? t('moduleConfig.editField') : t('moduleConfig.newField')"
      @close="showModal = false"
    >
      <div class="space-y-1.5">
        <Label>{{ t('moduleConfig.label') }}</Label>
        <Input v-model="form.label" :placeholder="t('moduleConfig.labelPlaceholder')" />
      </div>
      <div v-if="!editing" class="space-y-1.5">
        <Label>{{ t('moduleConfig.keyOptional') }}</Label>
        <Input v-model="form.key" :placeholder="t('moduleConfig.keyPlaceholder')" />
      </div>
      <div class="space-y-1.5">
        <Label>{{ t('moduleConfig.type') }}</Label>
        <select v-model="form.type" :class="controlClass" :disabled="!!editing">
          <option v-for="ft in FIELD_TYPES" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
        </select>
      </div>
      <div v-if="TYPES_WITH_OPTIONS.includes(form.type as ModuleField['type'])" class="space-y-1.5">
        <Label>{{ t('moduleConfig.options') }}</Label>
        <Textarea v-model="form.optionsText" :placeholder="t('moduleConfig.optionsPlaceholder')" />
      </div>
      <label class="flex cursor-pointer items-center gap-2 text-sm">
        <Checkbox v-model:checked="form.required" />
        <span>{{ t('moduleConfig.requiredField') }}</span>
      </label>

      <template #footer>
        <Button variant="secondary" @click="showModal = false">{{ t('common.cancel') }}</Button>
        <Button :disabled="!form.label" @click="saveField">{{ t('common.save') }}</Button>
      </template>
    </BaseModal>
  </PageContainer>
</template>
