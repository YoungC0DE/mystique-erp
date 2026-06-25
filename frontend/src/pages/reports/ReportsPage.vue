<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Module, Report } from '@/types';
import { createEmptyFilter, type FieldFilter } from '@/lib/filters';
import { modulesService } from '@/services/modules.service';
import { reportsService, type ReportPayload } from '@/services/reports.service';
import { apiErrorMessage } from '@/services/http';
import { useToast } from '@/composables/useToast';
import ModuleFilterForm from '@/components/modules/ModuleFilterForm.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import BasePagination from '@/components/ui/BasePagination.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Icon } from '@/components/ui/icon';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const { t } = useI18n();
const toast = useToast();

const reports = ref<Report[]>([]);
const modules = ref<Module[]>([]);
const page = ref(1);
const lastPage = ref(1);

const showFormModal = ref(false);
const showRunModal = ref(false);
const editing = ref<Report | null>(null);
const runningReport = ref<Report | null>(null);
const runningModuleFields = ref<import('@/types').ModuleField[]>([]);
const runData = ref<Record<string, unknown>[]>([]);
const runPage = ref(1);
const runLastPage = ref(1);

const form = reactive<ReportPayload & { filtersDraft: FieldFilter[] }>({
  name: '',
  module_id: '',
  field_keys: [],
  filters: [],
  filtersDraft: [],
});

const selectedModule = ref<Module | null>(null);
const moduleFields = computed(() => (selectedModule.value?.fields ?? []).slice().sort((a, b) => a.order - b.order));

watch(
  () => form.module_id,
  async (moduleId, prevId) => {
    if (!moduleId) {
      selectedModule.value = null;
      return;
    }
    if (prevId && prevId !== moduleId) {
      form.filtersDraft = [];
    }
    try {
      selectedModule.value = await modulesService.get(moduleId);
    } catch (e) {
      toast.error(apiErrorMessage(e));
    }
  },
);

async function load(): Promise<void> {
  try {
    const [reportsRes, modulesRes] = await Promise.all([reportsService.list(page.value), modulesService.list(1, 100)]);
    reports.value = reportsRes.data;
    lastPage.value = reportsRes.meta.last_page;
    modules.value = modulesRes.data;
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

function openCreate(): void {
  editing.value = null;
  const firstId = modules.value[0]?.id ?? '';
  Object.assign(form, {
    name: '',
    module_id: firstId,
    field_keys: [],
    filters: [],
    filtersDraft: [],
  });
  if (firstId) {
    modulesService.get(firstId).then((m) => {
      selectedModule.value = m;
    });
  }
  showFormModal.value = true;
}

async function openEdit(report: Report): Promise<void> {
  editing.value = report;
  const full = await reportsService.get(report.id);
  Object.assign(form, {
    name: full.name,
    module_id: full.module_id,
    field_keys: [...full.field_keys],
    filters: full.filters ?? [],
    filtersDraft: (full.filters ?? []).map((f) => ({ ...createEmptyFilter(), ...f, id: crypto.randomUUID() })),
  });
  selectedModule.value = await modulesService.get(full.module_id);
  showFormModal.value = true;
}

function toggleField(key: string, checked: boolean): void {
  if (checked) {
    if (!form.field_keys.includes(key)) form.field_keys.push(key);
  } else {
    form.field_keys = form.field_keys.filter((k) => k !== key);
  }
}

function moveField(index: number, dir: -1 | 1): void {
  const target = index + dir;
  if (target < 0 || target >= form.field_keys.length) return;
  const arr = [...form.field_keys];
  [arr[index], arr[target]] = [arr[target], arr[index]];
  form.field_keys = arr;
}

async function saveReport(): Promise<void> {
  if (!form.name || !form.module_id || form.field_keys.length === 0) {
    toast.error(t('reports.validation'));
    return;
  }
  try {
    const payload: ReportPayload = {
      name: form.name,
      module_id: form.module_id,
      field_keys: form.field_keys,
      filters: form.filtersDraft.filter((f) => f.field),
    };
    if (editing.value) {
      await reportsService.update(editing.value.id, payload);
      toast.success(t('reports.updated'));
    } else {
      await reportsService.create(payload);
      toast.success(t('reports.created'));
    }
    showFormModal.value = false;
    await load();
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function removeReport(report: Report): Promise<void> {
  if (!confirm(t('reports.confirmRemove', { name: report.name }))) return;
  try {
    await reportsService.remove(report.id);
    toast.success(t('reports.removed'));
    await load();
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function runReport(report: Report, p = 1): Promise<void> {
  runningReport.value = report;
  showRunModal.value = true;
  runPage.value = p;
  try {
    const full = await reportsService.get(report.id);
    runningReport.value = full;
    if (full.module_id) {
      const mod = await modulesService.get(full.module_id);
      runningModuleFields.value = (mod.fields ?? []).slice().sort((a, b) => a.order - b.order);
    }
    const result = await reportsService.run(report.id, p);
    runData.value = result.data;
    runLastPage.value = result.meta.last_page;
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

onMounted(load);
</script>

<template>
  <PageContainer>
    <PageHeader :title="t('reports.title')" :subtitle="t('reports.subtitle')">
      <template #actions>
        <Button class="gap-1.5" @click="openCreate">
          <Icon name="plus" :size="18" />
          {{ t('reports.new') }}
        </Button>
      </template>
    </PageHeader>

    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>{{ t('reports.name') }}</TableHead>
          <TableHead>{{ t('reports.module') }}</TableHead>
          <TableHead>{{ t('reports.fieldsCount') }}</TableHead>
          <TableHead class="w-[200px]" />
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-for="report in reports" :key="report.id">
          <TableCell>
            <strong>{{ report.name }}</strong>
          </TableCell>
          <TableCell>{{ report.module?.name ?? '—' }}</TableCell>
          <TableCell>{{ report.field_keys.length }}</TableCell>
          <TableCell>
            <div class="flex justify-end gap-1">
              <Button variant="ghost" size="sm" @click="runReport(report)">{{ t('reports.run') }}</Button>
              <Button variant="ghost" size="sm" @click="openEdit(report)">{{ t('common.edit') }}</Button>
              <Button variant="ghost" size="sm" class="text-danger" @click="removeReport(report)">
                {{ t('common.delete') }}
              </Button>
            </div>
          </TableCell>
        </TableRow>
        <TableRow v-if="reports.length === 0">
          <TableCell colspan="4" class="py-12 text-center text-muted-foreground">
            {{ t('reports.empty') }}
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <BasePagination
      v-if="lastPage > 1"
      class="mt-4"
      :current-page="page"
      :last-page="lastPage"
      @change="
        (p) => {
          page = p;
          load();
        }
      "
    />

    <BaseModal
      v-if="showFormModal"
      large
      :title="editing ? t('reports.edit') : t('reports.new')"
      @close="showFormModal = false"
    >
      <div class="space-y-4">
        <div class="space-y-1.5">
          <Label>{{ t('reports.name') }}</Label>
          <Input v-model="form.name" :placeholder="t('reports.namePlaceholder')" />
        </div>

        <div class="space-y-1.5">
          <Label>{{ t('reports.module') }}</Label>
          <select
            v-model="form.module_id"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
          >
            <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>

        <div>
          <Label class="mb-2 block">{{ t('reports.selectFields') }}</Label>
          <div class="max-h-48 space-y-2 overflow-y-auto rounded-md border p-3">
            <label v-for="f in moduleFields" :key="f.id" class="flex items-center gap-2 text-sm">
              <Checkbox
                :checked="form.field_keys.includes(f.key)"
                @update:checked="(v: boolean | 'indeterminate') => toggleField(f.key, v === true)"
              />
              <span>{{ f.label }}</span>
            </label>
          </div>
        </div>

        <div v-if="form.field_keys.length">
          <Label class="mb-2 block">{{ t('reports.fieldOrder') }}</Label>
          <div class="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-border/80 p-2">
            <div
              v-for="(key, i) in form.field_keys"
              :key="key"
              class="flex items-center gap-2 rounded border px-2 py-1 text-sm"
            >
              <span class="flex-1">{{ moduleFields.find((f) => f.key === key)?.label ?? key }}</span>
              <Button variant="ghost" size="icon" class="h-7 w-7" :disabled="i === 0" @click="moveField(i, -1)">
                <Icon name="chevron-up" :size="18" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :disabled="i === form.field_keys.length - 1"
                @click="moveField(i, 1)"
              >
                <Icon name="chevron-down" :size="18" />
              </Button>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <div class="mb-3 flex items-center justify-between gap-2">
            <div>
              <Label class="block">{{ t('reports.configureFilters') }}</Label>
              <p class="mt-1 text-sm text-muted-foreground">{{ t('filters.subtitle') }}</p>
            </div>
            <Button v-if="form.filtersDraft.length" variant="ghost" size="sm" @click="form.filtersDraft = []">
              {{ t('filters.clear') }}
            </Button>
          </div>
          <ModuleFilterForm v-model="form.filtersDraft" :fields="moduleFields" />
        </div>
      </div>

      <template #footer>
        <Button variant="secondary" @click="showFormModal = false">{{ t('common.cancel') }}</Button>
        <Button @click="saveReport">{{ t('common.save') }}</Button>
      </template>
    </BaseModal>

    <BaseModal v-if="showRunModal && runningReport" large :title="runningReport.name" @close="showRunModal = false">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead v-for="key in runningReport.field_keys" :key="key">
              {{ runningModuleFields.find((f) => f.key === key)?.label ?? key }}
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="(row, idx) in runData" :key="idx">
            <TableCell v-for="key in runningReport.field_keys" :key="key">
              {{ row[key] ?? '—' }}
            </TableCell>
          </TableRow>
          <TableRow v-if="runData.length === 0">
            <TableCell :colspan="runningReport.field_keys.length" class="py-8 text-center text-muted-foreground">
              {{ t('reports.noResults') }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
      <BasePagination
        v-if="runLastPage > 1"
        class="mt-4"
        :current-page="runPage"
        :last-page="runLastPage"
        @change="(p) => runningReport && runReport(runningReport, p)"
      />
    </BaseModal>
  </PageContainer>
</template>
