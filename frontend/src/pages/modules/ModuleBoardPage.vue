<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import type { KanbanBoard, Module, ModuleField, ModuleRecord, RecordValue } from '@/types'
import type { FieldFilter } from '@/lib/filters'
import { modulesService } from '@/services/modules.service'
import { recordsService, type KanbanFilters } from '@/services/records.service'
import { apiErrorMessage } from '@/services/http'
import { getEcho } from '@/services/echo'
import { useAuthStore } from '@/stores/auth'
import { useModulesStore } from '@/stores/modules'
import { useToast } from '@/composables/useToast'
import KanbanColumn from '@/components/kanban/KanbanColumn.vue'
import DynamicForm from '@/components/form/DynamicForm.vue'
import RecordDetailModal from '@/components/modules/RecordDetailModal.vue'
import ModuleFilterDropdown from '@/components/modules/ModuleFilterDropdown.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Icon } from '@/components/ui/icon'
import { controlClass } from '@/lib/inputStyles'

const props = defineProps<{ slug: string }>()
const { t } = useI18n()
const auth = useAuthStore()
const modulesStore = useModulesStore()
const toast = useToast()
const router = useRouter()

const module = ref<Module | null>(null)
const fields = ref<ModuleField[]>([])
const board = ref<KanbanBoard | null>(null)

const filters = ref<{ q: string; created_by: string }>({ q: '', created_by: '' })
const fieldFilters = ref<FieldFilter[]>([])
const pages = ref<Record<string, number>>({})
const perPage = 10

let channelName: string | null = null
let searchTimer: ReturnType<typeof setTimeout> | null = null

const canCreate = computed(() => auth.can('create') && !module.value?.is_integrated)
const canMove = computed(() => auth.can('update'))
const isIntegrated = computed(() => module.value?.is_integrated ?? false)

const totalRecords = computed(
  () => board.value?.columns.reduce((acc, c) => acc + c.meta.total, 0) ?? 0,
)

const activeFilterCount = computed(() => fieldFilters.value.filter((f) => f.field).length)

async function resolveModule(): Promise<void> {
  let mod = modulesStore.findBySlug(props.slug)
  if (!mod) {
    await modulesStore.loadAllowed()
    mod = modulesStore.findBySlug(props.slug)
  }
  if (!mod) {
    toast.error(t('board.moduleNotFound'))
    router.push({ name: 'dashboard' })
    return
  }
  module.value = await modulesService.get(mod.id)
  fields.value = (module.value.fields ?? []).slice().sort((a, b) => a.order - b.order)
}

function buildFilters(): KanbanFilters {
  const f: KanbanFilters = { per_page: perPage }
  if (filters.value.q) f.q = filters.value.q
  if (filters.value.created_by) f.created_by = filters.value.created_by
  if (fieldFilters.value.length) f.filters = fieldFilters.value
  for (const [col, page] of Object.entries(pages.value)) {
    if (page > 1) f[`${col}_page`] = page
  }
  return f
}

async function loadBoard(silent = false): Promise<void> {
  if (!module.value) return
  try {
    board.value = await recordsService.kanban(module.value.id, buildFilters(), {
      skipGlobalLoading: silent,
    })
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

function subscribe(): void {
  if (!module.value) return
  channelName = `module.${module.value.id}`
  getEcho()
    .private(channelName)
    .listen('.record.moved', () => {
      loadBoard(true)
    })
}

function unsubscribe(): void {
  if (channelName) {
    getEcho().leave(channelName)
    channelName = null
  }
}

const dragging = ref<ModuleRecord | null>(null)

async function onDrop(payload: { status: string }): Promise<void> {
  const record = dragging.value
  dragging.value = null
  if (!record || record.status === payload.status || !module.value) return

  const from = record.status
  const to = payload.status
  applyLocalMove(record.id, from, to)
  try {
    if (isIntegrated.value) {
      await recordsService.moveIntegrated(module.value.id, record.id, to)
    } else {
      await recordsService.move(record.id, to)
    }
  } catch (e) {
    applyLocalMove(record.id, to, from)
    toast.error(apiErrorMessage(e))
  }
}

function applyLocalMove(recordId: string, from: string, to: string): void {
  if (!board.value) return
  const fromCol = board.value.columns.find((c) => c.key === from)
  const toCol = board.value.columns.find((c) => c.key === to)
  if (!fromCol || !toCol) return
  const idx = fromCol.records.findIndex((r) => r.id === recordId)
  if (idx === -1) return
  const [rec] = fromCol.records.splice(idx, 1)
  rec.status = to
  toCol.records.unshift(rec)
  fromCol.meta.total = Math.max(0, fromCol.meta.total - 1)
  toCol.meta.total += 1
}

function changePage(payload: { status: string; page: number }): void {
  pages.value = { ...pages.value, [payload.status]: payload.page }
  loadBoard(true)
}

function applyFieldFilters(): void {
  pages.value = {}
  loadBoard(true)
}

function clearFieldFilters(): void {
  fieldFilters.value = []
  pages.value = {}
  loadBoard(true)
}

const showEditModal = ref(false)
const showDetailModal = ref(false)
const detailRecord = ref<ModuleRecord | null>(null)
const editing = ref<ModuleRecord | null>(null)
const recordForm = ref<Record<string, RecordValue>>({})
const recordStatus = ref<string>('inputar')
const formRef = ref<InstanceType<typeof DynamicForm> | null>(null)

function openCreate(): void {
  editing.value = null
  recordForm.value = {}
  recordStatus.value = module.value?.statuses?.[0]?.slug ?? 'inputar'
  showEditModal.value = true
}

function openRecord(record: ModuleRecord): void {
  detailRecord.value = record
  showDetailModal.value = true
}

function onDetailStatusChanged(payload: { recordId: string; from: string; to: string }): void {
  applyLocalMove(payload.recordId, payload.from, payload.to)
  if (detailRecord.value?.id === payload.recordId) {
    detailRecord.value = { ...detailRecord.value, status: payload.to }
  }
}

async function saveRecord(): Promise<void> {
  if (!module.value) return
  if (formRef.value && !formRef.value.validate()) {
    toast.error(t('board.requiredFields'))
    return
  }
  try {
    if (editing.value) {
      await recordsService.update(editing.value.id, {
        status: recordStatus.value,
        values: recordForm.value,
      })
      toast.success(t('board.recordUpdated'))
    } else {
      await recordsService.create(module.value.id, {
        status: recordStatus.value,
        values: recordForm.value,
      })
      toast.success(t('board.recordCreated'))
    }
    showEditModal.value = false
    await loadBoard(true)
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function deleteRecord(): Promise<void> {
  if (!editing.value) return
  if (!confirm(t('board.confirmRemove'))) return
  try {
    await recordsService.remove(editing.value.id)
    toast.success(t('board.recordRemoved'))
    showEditModal.value = false
    await loadBoard(true)
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

watch(
  () => filters.value.q,
  () => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
      pages.value = {}
      loadBoard(true)
    }, 350)
  },
)

watch(
  () => props.slug,
  async () => {
    unsubscribe()
    await init()
  },
)

async function init(): Promise<void> {
  fieldFilters.value = []
  await resolveModule()
  if (module.value) {
    await loadBoard()
    subscribe()
  }
}

onMounted(init)
onBeforeUnmount(unsubscribe)
</script>

<template>
  <div class="flex h-full flex-col animate-page-in">
    <div class="relative z-20 flex flex-wrap items-center justify-between gap-4 border-b border-border/60 bg-card/50 px-6 py-5 backdrop-blur-sm lg:px-8">
      <div class="flex flex-wrap items-center gap-4">
        <ModuleFilterDropdown
          v-model="fieldFilters"
          :fields="fields"
          @apply="applyFieldFilters"
        />
        <div>
          <h1 class="text-xl font-semibold tracking-tight">{{ module?.name ?? '...' }}</h1>
          <p class="mt-0.5 text-sm text-muted-foreground">
            {{ t('board.recordsCount', { count: totalRecords }) }}
          </p>
          <p v-if="isIntegrated" class="mt-1 text-xs text-muted-foreground">
            {{ t('board.readOnlyHint') }}
          </p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Input
          v-model="filters.q"
          class="w-[220px]"
          :placeholder="t('board.searchPlaceholder')"
        />
        <Button
          v-if="auth.can('update')"
          variant="secondary"
          class="gap-1.5"
          @click="router.push({ name: 'module-config', params: { slug } })"
        >
          <Icon name="settings" :size="18" />
          {{ t('board.configure') }}
        </Button>
        <Button v-if="canCreate" class="gap-1.5" @click="openCreate">
          <Icon name="plus" :size="18" />
          {{ t('board.newRecord') }}
        </Button>
      </div>
    </div>

    <div v-if="activeFilterCount" class="relative z-20 flex flex-wrap items-center gap-2 px-6 pb-3">
      <span class="text-xs text-muted-foreground">{{ t('board.activeFilters') }}</span>
      <Badge v-for="f in fieldFilters" :key="f.id" variant="muted">
        {{ fields.find((x) => x.key === f.field)?.label ?? f.field }}
        · {{ t(`filters.operators.${f.operator}`) }}
      </Badge>
      <Button variant="ghost" size="sm" @click="clearFieldFilters">{{ t('filters.clear') }}</Button>
    </div>

    <div v-if="board" class="relative z-0 flex-1 overflow-hidden px-6 pb-6 lg:px-8">
      <div class="flex h-full gap-4 overflow-x-auto pb-2 pt-1">
        <KanbanColumn
          v-for="col in board.columns"
          :key="col.key"
          :column="col"
          :fields="fields"
          :can-move="canMove"
          :read-only="isIntegrated"
          @open="openRecord"
          @drop="onDrop"
          @dragstart="dragging = $event"
          @dragend="dragging = null"
          @page="changePage"
        />
      </div>
    </div>

    <RecordDetailModal
      v-if="showDetailModal && detailRecord && module"
      :record="detailRecord"
      :fields="fields"
      :detail-layout="module.detail_layout"
      :statuses="module.statuses"
      :module-id="module.id"
      :is-integrated="isIntegrated"
      :can-move-status="canMove"
      @status-changed="onDetailStatusChanged"
      @close="showDetailModal = false"
    />

    <BaseModal
      v-if="showEditModal && !isIntegrated"
      large
      :title="editing ? t('board.editRecord') : t('board.newRecord')"
      @close="showEditModal = false"
    >
      <div class="space-y-1.5">
        <Label>{{ t('board.columnStatus') }}</Label>
        <select v-model="recordStatus" :class="controlClass">
          <option v-for="col in board?.columns ?? []" :key="col.key" :value="col.key">
            {{ col.label }}
          </option>
        </select>
      </div>
      <Separator class="my-4" />
      <DynamicForm ref="formRef" v-model="recordForm" :fields="fields" />

      <template #footer>
        <Button
          v-if="editing && auth.can('delete')"
          variant="danger"
          class="mr-auto"
          @click="deleteRecord"
        >
          {{ t('common.delete') }}
        </Button>
        <Button variant="secondary" @click="showEditModal = false">{{ t('common.cancel') }}</Button>
        <Button @click="saveRecord">{{ t('common.save') }}</Button>
      </template>
    </BaseModal>
  </div>
</template>
