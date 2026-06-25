<script setup lang="ts">

import { onMounted, reactive, ref } from 'vue'

import { useI18n } from 'vue-i18n'

import type { Role, User } from '@/types'

import { usersService, type UserPayload } from '@/services/users.service'

import { rolesService } from '@/services/roles.service'

import { apiErrorMessage } from '@/services/http'

import { useAuthStore } from '@/stores/auth'

import { useToast } from '@/composables/useToast'

import BaseModal from '@/components/ui/BaseModal.vue'

import BasePagination from '@/components/ui/BasePagination.vue'

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



const users = ref<User[]>([])

const roles = ref<Role[]>([])

const page = ref(1)

const lastPage = ref(1)

const total = ref(0)



const showModal = ref(false)

const editing = ref<User | null>(null)

const form = reactive<UserPayload>({

  name: '',

  email: '',

  password: '',

  is_admin: false,

  roles: [],

})



async function load(): Promise<void> {

  try {

    const res = await usersService.list(page.value)

    users.value = res.data

    lastPage.value = res.meta.last_page

    total.value = res.meta.total

  } catch (e) {

    toast.error(apiErrorMessage(e))

  }

}



async function loadRefs(): Promise<void> {

  try {

    roles.value = (await rolesService.list()).data

  } catch {

    /* refs opcionais */

  }

}



function openCreate(): void {

  editing.value = null

  Object.assign(form, {

    name: '',

    email: '',

    password: '',

    is_admin: false,

    roles: [],

  })

  showModal.value = true

}



function openEdit(user: User): void {

  editing.value = user

  Object.assign(form, {

    name: user.name,

    email: user.email,

    password: '',

    is_admin: user.is_admin,

    roles: user.roles?.map((r) => r.id) ?? [],

  })

  showModal.value = true

}



function toggleRole(roleId: string, checked: boolean): void {

  const ids = form.roles ?? []

  if (checked) {

    if (!ids.includes(roleId)) form.roles = [...ids, roleId]

  } else {

    form.roles = ids.filter((id) => id !== roleId)

  }

}



async function save(): Promise<void> {
  try {
    const payload: UserPayload = { ...form }
    if (editing.value && !payload.password) delete payload.password
    if (!auth.isAdmin) delete payload.is_admin

    if (editing.value) {
      await usersService.update(editing.value.id, payload)
      toast.success(t('users.updated'))
    } else {
      await usersService.create(payload)
      toast.success(t('users.created'))
    }
    showModal.value = false
    await load()
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}



async function remove(user: User): Promise<void> {

  if (!confirm(t('users.confirmRemove', { name: user.name }))) return

  try {

    await usersService.remove(user.id)

    toast.success(t('users.removed'))

    await load()

  } catch (e) {

    toast.error(apiErrorMessage(e))

  }

}



function changePage(p: number): void {

  page.value = p

  load()

}



onMounted(() => {

  load()

  loadRefs()

})

</script>



<template>

  <PageContainer>

    <PageHeader :title="t('users.title')" :subtitle="t('users.subtitle')">

      <template #actions>

        <Button v-if="auth.can('create')" @click="openCreate">+ {{ t('users.newUser') }}</Button>

      </template>

    </PageHeader>



    <Table>

      <TableHeader>

        <TableRow>

          <TableHead>{{ t('users.name') }}</TableHead>

          <TableHead>{{ t('users.email') }}</TableHead>

          <TableHead>{{ t('users.groups') }}</TableHead>

          <TableHead>{{ t('users.profile') }}</TableHead>

          <TableHead class="w-[120px]" />

        </TableRow>

      </TableHeader>

      <TableBody>

        <TableRow v-for="u in users" :key="u.id">

          <TableCell><strong>{{ u.name }}</strong></TableCell>

          <TableCell class="text-muted-foreground">{{ u.email }}</TableCell>

          <TableCell>

            <Badge

              v-for="r in u.roles ?? []"

              :key="r.id"

              variant="muted"

              class="mr-1"

            >

              {{ r.name }}

            </Badge>

            <span v-if="!u.roles?.length" class="text-muted-foreground">—</span>

          </TableCell>

          <TableCell>

            <Badge v-if="u.is_admin" variant="primary">{{ t('topbar.admin') }}</Badge>

            <Badge v-else variant="muted">{{ t('users.userRole') }}</Badge>

          </TableCell>

          <TableCell>

            <div class="flex justify-end gap-1.5">

              <Button v-if="auth.can('update')" variant="ghost" size="sm" @click="openEdit(u)">

                {{ t('common.edit') }}

              </Button>

              <Button

                v-if="auth.can('delete')"

                variant="ghost"

                size="sm"

                class="text-danger hover:text-danger"

                @click="remove(u)"

              >

                {{ t('common.delete') }}

              </Button>

            </div>

          </TableCell>

        </TableRow>

        <TableRow v-if="users.length === 0">

          <TableCell colspan="5" class="py-12 text-center text-muted-foreground">

            {{ t('users.empty') }}

          </TableCell>

        </TableRow>

      </TableBody>

    </Table>



    <BasePagination :current-page="page" :last-page="lastPage" :total="total" @change="changePage" />



    <BaseModal

      v-if="showModal"

      :title="editing ? t('users.editUser') : t('users.newUser')"

      @close="showModal = false"

    >

      <div class="space-y-1.5">

        <Label>{{ t('users.name') }}</Label>

        <Input v-model="form.name" />

      </div>

      <div class="space-y-1.5">

        <Label>{{ t('users.email') }}</Label>

        <Input v-model="form.email" type="email" />

      </div>

      <div class="space-y-1.5">

        <Label>

          {{ t('users.password') }}

          <span v-if="editing" class="font-normal text-muted-foreground">{{ t('users.passwordHint') }}</span>

        </Label>

        <Input v-model="form.password" type="password" autocomplete="new-password" />

      </div>

      <div class="space-y-1.5">

        <Label>{{ t('users.groups') }}</Label>

        <div class="flex max-h-40 flex-col gap-1.5 overflow-y-auto rounded-md border border-border p-3">

          <label

            v-for="r in roles"

            :key="r.id"

            class="flex cursor-pointer items-center gap-2 text-sm"

          >

            <Checkbox

              :checked="form.roles?.includes(r.id)"

              @update:checked="(v: boolean | 'indeterminate') => toggleRole(r.id, v === true)"

            />

            <span>{{ r.name }}</span>

          </label>

          <span v-if="roles.length === 0" class="text-sm text-muted-foreground">{{ t('users.noGroups') }}</span>

        </div>

      </div>

      <label v-if="auth.isAdmin" class="flex cursor-pointer items-center gap-2 text-sm">

        <Checkbox v-model:checked="form.is_admin" />

        <span>{{ t('users.admin') }}</span>

      </label>



      <template #footer>

        <Button variant="secondary" @click="showModal = false">{{ t('common.cancel') }}</Button>

        <Button @click="save">{{ t('common.save') }}</Button>

      </template>

    </BaseModal>

  </PageContainer>

</template>

