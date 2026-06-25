<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import type { DatabaseColumn, DatabaseConnection, Module } from '@/types';
import {
  DEFAULT_MODULE_STATUSES,
  modulesService,
  type ModulePayload,
  type ModuleStatusPayload,
} from '@/services/modules.service';
import { connectionsService } from '@/services/connections.service';
import { apiErrorMessage } from '@/services/http';
import { useAuthStore } from '@/stores/auth';
import { useModulesStore } from '@/stores/modules';
import { useToast } from '@/composables/useToast';
import BaseModal from '@/components/ui/BaseModal.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Icon } from '@/components/ui/icon';
import { DEFAULT_MODULE_ICON } from '@/lib/icon';
import { controlClass } from '@/lib/inputStyles';

const { t } = useI18n();
const auth = useAuthStore();
const modulesStore = useModulesStore();
const toast = useToast();
const router = useRouter();

const modules = ref<Module[]>([]);
const connections = ref<DatabaseConnection[]>([]);
const tableColumns = ref<DatabaseColumn[]>([]);
const showModal = ref(false);
const editing = ref<Module | null>(null);
const selectedColumns = ref<string[]>([]);
const statuses = ref<ModuleStatusPayload[]>([]);

interface ModuleFormState {
  name: string;
  icon: string;
  status: string;
  connection_id: string;
  callback_url: string;
  callback_method: string;
  status_column: string;
}

const form = reactive<ModuleFormState>({
  name: '',
  icon: DEFAULT_MODULE_ICON,
  status: 'active',
  connection_id: '',
  callback_url: '',
  callback_method: 'POST',
  status_column: '',
});

const STATUS_COLUMN_NAMES = ['status', 'etapa', 'stage', 'situacao', 'fase'];

const canSave = computed(
  () =>
    !!form.name.trim() &&
    !!form.connection_id &&
    selectedColumns.value.length > 0 &&
    !!form.status_column &&
    statuses.value.length > 0,
);

const saveBlockers = computed(() => {
  const missing: string[] = [];
  if (!form.name.trim()) missing.push(t('modulesAdmin.name'));
  if (!form.connection_id) missing.push(t('modulesAdmin.connection'));
  if (!form.status_column) missing.push(t('modulesAdmin.statusColumn'));
  if (selectedColumns.value.length === 0) missing.push(t('modulesAdmin.columns'));
  return missing;
});

async function load(): Promise<void> {
  try {
    modules.value = (await modulesService.list()).data;
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function loadConnections(): Promise<void> {
  try {
    connections.value = await connectionsService.list();
  } catch {
    connections.value = [];
  }
}

async function loadTableColumns(connectionId: string): Promise<void> {
  if (!connectionId) {
    tableColumns.value = [];
    return;
  }

  try {
    tableColumns.value = await connectionsService.columns(connectionId);
    suggestStatusColumn();
  } catch (e) {
    tableColumns.value = [];
    toast.error(apiErrorMessage(e));
  }
}

function resetIntegrationForm(): void {
  Object.assign(form, {
    name: '',
    icon: DEFAULT_MODULE_ICON,
    status: 'active',
    connection_id: '',
    callback_url: '',
    callback_method: 'POST',
    status_column: '',
  });
  selectedColumns.value = [];
  statuses.value = DEFAULT_MODULE_STATUSES.map((s) => ({ ...s }));
  tableColumns.value = [];
}

function openCreate(): void {
  editing.value = null;
  resetIntegrationForm();
  showModal.value = true;
}

async function openEdit(mod: Module): Promise<void> {
  editing.value = mod;
  try {
    const full = await modulesService.get(mod.id);
    Object.assign(form, {
      name: full.name,
      icon: full.icon ?? '',
      status: full.status,
      connection_id: full.connection_id ?? '',
      callback_url: full.callback_url ?? '',
      callback_method: full.callback_method ?? 'POST',
      status_column: full.status_column ?? '',
    });
    selectedColumns.value = full.fields?.map((f) => f.key) ?? [];
    statuses.value =
      full.statuses?.map((s) => ({
        slug: s.slug,
        label: s.label,
        order: s.order,
        external_value: s.external_value,
      })) ?? DEFAULT_MODULE_STATUSES.map((s) => ({ ...s }));

    if (form.connection_id) {
      await loadTableColumns(form.connection_id);
    }
    showModal.value = true;
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

function ensureStatusColumnSelected(name: string): void {
  if (name && !selectedColumns.value.includes(name)) {
    selectedColumns.value = [...selectedColumns.value, name];
  }
}

function suggestStatusColumn(): void {
  if (form.status_column) return;

  const match = tableColumns.value.find((col) => STATUS_COLUMN_NAMES.includes(col.name.toLowerCase()));
  if (match) {
    form.status_column = match.name;
    ensureStatusColumnSelected(match.name);
  }
}

function onStatusColumnChange(): void {
  if (form.status_column) {
    ensureStatusColumnSelected(form.status_column);
  }
}

function toggleColumn(name: string, checked: boolean): void {
  if (checked) {
    if (!selectedColumns.value.includes(name)) {
      selectedColumns.value = [...selectedColumns.value, name];
    }
  } else {
    selectedColumns.value = selectedColumns.value.filter((c) => c !== name);
    if (form.status_column === name) form.status_column = '';
  }
}

function selectAllColumns(): void {
  selectedColumns.value = tableColumns.value.map((col) => col.name);
}

function clearColumnSelection(): void {
  selectedColumns.value = form.status_column ? [form.status_column] : [];
}

function addStatus(): void {
  const order = statuses.value.length;
  statuses.value.push({
    slug: `status_${order}`,
    label: '',
    order,
    external_value: '',
  });
}

function removeStatus(index: number): void {
  statuses.value.splice(index, 1);
  statuses.value.forEach((s, i) => (s.order = i));
}

function buildPayload(): ModulePayload {
  return {
    name: form.name,
    icon: form.icon || null,
    status: form.status,
    connection_id: form.connection_id,
    callback_url: form.callback_url || null,
    callback_method: form.callback_method,
    status_column: form.status_column,
    columns: selectedColumns.value.map((name, index) => {
      const existing = editing.value?.fields?.find((f) => f.key === name);
      return {
        name,
        label: existing?.label ?? name,
        type: existing?.type,
        order: index,
        show_in_card: true,
        show_in_list: true,
        visible: true,
      };
    }),
    statuses: statuses.value.map((s, index) => ({ ...s, order: index })),
  };
}

async function save(): Promise<void> {
  try {
    const payload = buildPayload();
    if (editing.value) {
      await modulesService.update(editing.value.id, payload);
      toast.success(t('modulesAdmin.updated'));
    } else {
      await modulesService.create(payload);
      toast.success(t('modulesAdmin.created'));
    }
    showModal.value = false;
    await load();
    await modulesStore.loadAllowed(true);
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

function openModule(mod: Module): void {
  router.push({ name: 'module', params: { slug: mod.slug } });
}

async function remove(mod: Module): Promise<void> {
  if (!confirm(t('modulesAdmin.confirmRemove', { name: mod.name }))) return;
  try {
    await modulesService.remove(mod.id);
    toast.success(t('modulesAdmin.removed'));
    await load();
    await modulesStore.loadAllowed(true);
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

watch(
  () => form.connection_id,
  async (id, prev) => {
    if (!showModal.value) return;
    if (id === prev) return;
    if (!id) {
      tableColumns.value = [];
      return;
    }
    await loadTableColumns(id);
    if (!editing.value || id !== editing.value.connection_id) {
      selectedColumns.value = [];
      form.status_column = '';
    }
  },
);

onMounted(() => {
  load();
  loadConnections();
});
</script>

<template>
  <PageContainer>
    <PageHeader :title="t('modulesAdmin.title')" :subtitle="t('modulesAdmin.subtitle')">
      <template #actions>
        <Button v-if="auth.can('create')" class="gap-1.5" @click="openCreate">
          <Icon name="plus" :size="18" />
          {{ t('modulesAdmin.newModule') }}
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-4">
      <Card
        v-for="mod in modules"
        :key="mod.id"
        class="cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card-hover"
        @click="openModule(mod)"
      >
        <CardContent class="flex min-h-[168px] flex-col gap-2.5 p-5">
          <div class="flex items-start justify-between gap-2">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
              <Icon :name="mod.icon ?? ''" :size="22" />
            </span>
            <div class="flex min-w-0 items-start gap-1">
              <div class="flex flex-wrap justify-end gap-1.5">
                <Badge :variant="mod.is_integrated ? 'default' : 'muted'">
                  {{ mod.is_integrated ? t('modulesAdmin.integrated') : t('modulesAdmin.notIntegrated') }}
                </Badge>
                <Badge :variant="mod.status === 'active' ? 'success' : 'muted'">
                  {{ mod.status === 'active' ? t('modulesAdmin.active') : t('modulesAdmin.inactive') }}
                </Badge>
              </div>
              <DropdownMenu v-if="auth.can('update') || auth.can('delete')">
                <DropdownMenuTrigger as-child>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 shrink-0 text-muted-foreground"
                    :aria-label="t('modulesAdmin.openActionsMenu')"
                    @click.stop
                  >
                    <Icon name="ellipsis-vertical" :size="18" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44" @click.stop>
                  <DropdownMenuItem
                    v-if="auth.can('update')"
                    @select="router.push({ name: 'module-config', params: { slug: mod.slug } })"
                  >
                    <Icon name="settings" :size="16" class="mr-2 opacity-60" />
                    {{ t('modulesAdmin.configure') }}
                  </DropdownMenuItem>
                  <DropdownMenuItem v-if="auth.can('update')" @select="openEdit(mod)">
                    <Icon name="pencil" :size="16" class="mr-2 opacity-60" />
                    {{ t('common.edit') }}
                  </DropdownMenuItem>
                  <DropdownMenuSeparator v-if="auth.can('update') && auth.can('delete')" />
                  <DropdownMenuItem
                    v-if="auth.can('delete')"
                    class="text-destructive focus:text-destructive"
                    @select="remove(mod)"
                  >
                    <Icon name="trash-2" :size="16" class="mr-2 opacity-60" />
                    {{ t('common.delete') }}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
          <strong class="text-[15px] font-semibold tracking-tight">{{ mod.name }}</strong>
          <span class="text-sm text-muted-foreground">
            {{ t('modulesAdmin.fieldsCount', { count: mod.fields_count ?? 0 }) }} · {{ mod.slug }}
          </span>
          <span class="mt-auto pt-1 text-xs text-muted-foreground">{{ t('modulesAdmin.clickToAccess') }}</span>
        </CardContent>
      </Card>
      <Card v-if="modules.length === 0" class="col-span-full">
        <CardContent class="py-14 text-center text-muted-foreground">
          {{ t('modulesAdmin.empty') }}
        </CardContent>
      </Card>
    </div>

    <BaseModal
      v-if="showModal"
      :title="editing ? t('modulesAdmin.editModule') : t('modulesAdmin.newModule')"
      large
      @close="showModal = false"
    >
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="space-y-1.5 sm:col-span-2">
          <Label>{{ t('modulesAdmin.name') }}</Label>
          <Input v-model="form.name" :placeholder="t('modulesAdmin.namePlaceholder')" />
        </div>
        <div class="space-y-1.5">
          <Label>{{ t('modulesAdmin.icon') }}</Label>
          <Input v-model="form.icon" :placeholder="t('modulesAdmin.iconPlaceholder')" />
        </div>
        <div class="space-y-1.5">
          <Label>{{ t('modulesAdmin.status') }}</Label>
          <select v-model="form.status" :class="controlClass">
            <option value="active">{{ t('modulesAdmin.active') }}</option>
            <option value="inactive">{{ t('modulesAdmin.inactive') }}</option>
          </select>
        </div>
        <div class="space-y-1.5 sm:col-span-2">
          <Label>{{ t('modulesAdmin.connection') }}</Label>
          <select v-model="form.connection_id" :class="controlClass">
            <option value="">{{ t('modulesAdmin.connectionPlaceholder') }}</option>
            <option v-for="conn in connections" :key="conn.id" :value="conn.id">
              {{ conn.name }} ({{ conn.table_name }})
            </option>
          </select>
        </div>

        <div v-if="form.connection_id" class="space-y-2 sm:col-span-2">
          <div class="space-y-2 rounded-lg border-2 border-primary/25 bg-primary/5 p-4">
            <Label class="text-base font-semibold">{{ t('modulesAdmin.statusColumn') }}</Label>
            <p class="text-sm text-muted-foreground">{{ t('modulesAdmin.statusColumnHint') }}</p>
            <template v-if="tableColumns.length">
              <select v-model="form.status_column" :class="controlClass" @change="onStatusColumnChange">
                <option value="">{{ t('modulesAdmin.statusColumnPlaceholder') }}</option>
                <option v-for="col in tableColumns" :key="col.name" :value="col.name">
                  {{ col.name }} ({{ col.type }})
                </option>
              </select>
              <p v-if="form.status_column" class="text-xs text-muted-foreground">
                {{ t('modulesAdmin.statusColumnSelected', { column: form.status_column }) }}
              </p>
            </template>
            <p v-else class="text-sm text-muted-foreground">
              {{ t('modulesAdmin.columnsEmpty') }}
            </p>
          </div>
        </div>

        <div v-if="form.connection_id && tableColumns.length" class="space-y-2 sm:col-span-2">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
              <Label>{{ t('modulesAdmin.columns') }}</Label>
              <p class="text-xs text-muted-foreground">
                {{
                  t('modulesAdmin.columnsSelectedCount', {
                    selected: selectedColumns.length,
                    total: tableColumns.length,
                  })
                }}
              </p>
            </div>
            <div class="flex gap-2">
              <Button type="button" variant="outline" size="sm" @click="selectAllColumns">
                {{ t('modulesAdmin.selectAllColumns') }}
              </Button>
              <Button type="button" variant="ghost" size="sm" @click="clearColumnSelection">
                {{ t('modulesAdmin.clearColumns') }}
              </Button>
            </div>
          </div>
          <p class="text-xs text-muted-foreground">{{ t('modulesAdmin.columnsHint') }}</p>
          <div class="grid max-h-48 gap-1 overflow-y-auto rounded-md border p-2 sm:grid-cols-2">
            <label
              v-for="col in tableColumns"
              :key="col.name"
              class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted/50"
            >
              <input
                type="checkbox"
                class="size-4 shrink-0 rounded border border-input accent-primary"
                :checked="selectedColumns.includes(col.name)"
                @change="toggleColumn(col.name, ($event.target as HTMLInputElement).checked)"
                @click.stop
              />
              <span class="min-w-0 flex-1 truncate">{{ col.name }}</span>
              <span class="shrink-0 text-xs text-muted-foreground">({{ col.type }})</span>
              <Badge v-if="form.status_column === col.name" variant="default" class="shrink-0 text-[10px]">
                {{ t('modulesAdmin.statusColumnBadge') }}
              </Badge>
            </label>
          </div>
        </div>

        <div class="space-y-1.5 sm:col-span-2">
          <Label>{{ t('modulesAdmin.callbackUrl') }}</Label>
          <Input v-model="form.callback_url" :placeholder="t('modulesAdmin.callbackUrlPlaceholder')" />
        </div>
        <div class="space-y-1.5">
          <Label>{{ t('modulesAdmin.callbackMethod') }}</Label>
          <select v-model="form.callback_method" :class="controlClass">
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="PATCH">PATCH</option>
          </select>
        </div>

        <div class="space-y-2 sm:col-span-2">
          <div class="flex items-center justify-between">
            <div>
              <Label>{{ t('modulesAdmin.statusesTitle') }}</Label>
              <p class="text-xs text-muted-foreground">{{ t('modulesAdmin.statusesHint') }}</p>
            </div>
            <Button variant="outline" size="sm" @click="addStatus">{{ t('modulesAdmin.addStatus') }}</Button>
          </div>
          <div class="space-y-2">
            <div
              v-for="(status, index) in statuses"
              :key="index"
              class="grid gap-2 rounded-md border border-border p-3 sm:grid-cols-[1fr_1fr_auto]"
            >
              <Input v-model="status.label" :placeholder="t('modulesAdmin.statusLabel')" />
              <Input v-model="status.external_value" :placeholder="t('modulesAdmin.statusExternalValue')" />
              <Button
                variant="ghost"
                size="icon"
                class="text-destructive"
                :disabled="statuses.length <= 1"
                @click="removeStatus(index)"
              >
                <Icon name="trash-2" :size="18" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <p v-if="saveBlockers.length" class="mr-auto text-xs text-muted-foreground">
          {{ t('modulesAdmin.saveBlocked', { fields: saveBlockers.join(', ') }) }}
        </p>
        <Button variant="secondary" @click="showModal = false">{{ t('common.cancel') }}</Button>
        <Button :disabled="!canSave" @click="save">{{ t('common.save') }}</Button>
      </template>
    </BaseModal>
  </PageContainer>
</template>
