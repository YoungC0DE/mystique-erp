<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Permission, Role } from '@/types'
import { permissionsService, rolesService } from '@/services/roles.service'
import { apiErrorMessage } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import BaseModal from '@/components/ui/BaseModal.vue'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const { t } = useI18n()
const auth = useAuthStore()
const toast = useToast()

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])

const showModal = ref(false)
const editing = ref<Role | null>(null)
const form = reactive<{ name: string; permissions: string[] }>({ name: '', permissions: [] })

async function load(): Promise<void> {
  try {
    const [r, p] = await Promise.all([rolesService.list(), permissionsService.list()])
    roles.value = r.data
    permissions.value = p
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

function openCreate(): void {
  editing.value = null
  form.name = ''
  form.permissions = []
  showModal.value = true
}

async function openEdit(role: Role): Promise<void> {
  try {
    const full = await rolesService.get(role.id)
    editing.value = full
    form.name = full.name
    form.permissions = full.permissions?.map((p) => p.id) ?? []
    showModal.value = true
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

function togglePermission(permissionId: string, checked: boolean): void {
  if (checked) {
    if (!form.permissions.includes(permissionId)) form.permissions.push(permissionId)
  } else {
    form.permissions = form.permissions.filter((id) => id !== permissionId)
  }
}

async function save(): Promise<void> {
  try {
    const payload = { name: form.name, permissions: form.permissions }

    if (editing.value) {
      await rolesService.update(editing.value.id, payload)
      toast.success(t('roles.updated'))
    } else {
      await rolesService.create(payload)
      toast.success(t('roles.created'))
    }
    showModal.value = false
    await load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function remove(role: Role): Promise<void> {
  if (!confirm(t('roles.confirmRemove', { name: role.name }))) return
  try {
    await rolesService.remove(role.id)
    toast.success(t('roles.removed'))
    await load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

onMounted(load)
</script>

<template>
  <PageContainer>
    <PageHeader :title="t('roles.title')" :subtitle="t('roles.subtitle')">
      <template #actions>
        <Button v-if="auth.can('create')" @click="openCreate">+ {{ t('roles.newRole') }}</Button>
      </template>
    </PageHeader>

    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>{{ t('roles.name') }}</TableHead>
          <TableHead>{{ t('roles.slug') }}</TableHead>
          <TableHead>{{ t('roles.permissions') }}</TableHead>
          <TableHead class="w-[120px]" />
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-for="r in roles" :key="r.id">
          <TableCell><strong>{{ r.name }}</strong></TableCell>
          <TableCell class="text-muted-foreground">{{ r.slug }}</TableCell>
          <TableCell>
            <Badge
              v-for="p in r.permissions ?? []"
              :key="p.id"
              variant="primary"
              class="mr-1"
            >
              {{ p.name }}
            </Badge>
            <span v-if="!r.permissions?.length" class="text-muted-foreground">—</span>
          </TableCell>
          <TableCell>
            <div class="flex justify-end gap-1.5">
              <Button v-if="auth.can('update')" variant="ghost" size="sm" @click="openEdit(r)">
                {{ t('common.edit') }}
              </Button>
              <Button
                v-if="auth.can('delete')"
                variant="ghost"
                size="sm"
                class="text-danger hover:text-danger"
                @click="remove(r)"
              >
                {{ t('common.delete') }}
              </Button>
            </div>
          </TableCell>
        </TableRow>
        <TableRow v-if="roles.length === 0">
          <TableCell colspan="4" class="py-12 text-center text-muted-foreground">
            {{ t('roles.empty') }}
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <BaseModal
      v-if="showModal"
      :title="editing ? t('roles.editRole') : t('roles.newRole')"
      @close="showModal = false"
    >
      <div class="space-y-1.5">
        <Label>{{ t('roles.roleName') }}</Label>
        <Input v-model="form.name" :placeholder="t('roles.roleNamePlaceholder')" />
      </div>
      <div class="space-y-1.5">
        <Label>{{ t('roles.permissions') }}</Label>
        <div class="grid grid-cols-2 gap-2 rounded-md border border-border p-3">
          <label
            v-for="p in permissions"
            :key="p.id"
            class="flex cursor-pointer items-center gap-2 text-sm"
          >
            <Checkbox
              :checked="form.permissions.includes(p.id)"
              @update:checked="(v: boolean | 'indeterminate') => togglePermission(p.id, v === true)"
            />
            <span>{{ p.name }}</span>
          </label>
        </div>
      </div>
      <template #footer>
        <Button variant="secondary" @click="showModal = false">{{ t('common.cancel') }}</Button>
        <Button :disabled="!form.name" @click="save">{{ t('common.save') }}</Button>
      </template>
    </BaseModal>
  </PageContainer>
</template>
