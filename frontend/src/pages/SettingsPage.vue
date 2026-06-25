<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { DatabaseConnection } from '@/types';
import { connectionsService, type ConnectionPayload } from '@/services/connections.service';
import { apiErrorMessage } from '@/services/http';
import { useToast } from '@/composables/useToast';
import BaseModal from '@/components/ui/BaseModal.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const { t } = useI18n();
const toast = useToast();

const connections = ref<DatabaseConnection[]>([]);
const showModal = ref(false);
const editing = ref<DatabaseConnection | null>(null);

const form = reactive<ConnectionPayload>({
  name: '',
  host: '127.0.0.1',
  port: 3306,
  database: '',
  username: '',
  password: '',
  table_name: '',
});

async function load(): Promise<void> {
  try {
    connections.value = await connectionsService.list();
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

function openCreate(): void {
  editing.value = null;
  Object.assign(form, {
    name: '',
    host: '127.0.0.1',
    port: 3306,
    database: '',
    username: '',
    password: '',
    table_name: '',
  });
  showModal.value = true;
}

function openEdit(connection: DatabaseConnection): void {
  editing.value = connection;
  Object.assign(form, {
    name: connection.name,
    host: connection.host,
    port: connection.port,
    database: connection.database,
    username: connection.username,
    password: '',
    table_name: connection.table_name,
  });
  showModal.value = true;
}

async function testCurrent(): Promise<void> {
  try {
    if (editing.value) {
      await connectionsService.test(editing.value.id);
    } else {
      if (!form.password) {
        toast.error(t('connections.password') + ': ' + t('common.genericError'));
        return;
      }
      await connectionsService.validate({ ...form, password: form.password });
    }
    toast.success(t('settings.tested'));
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function save(): Promise<void> {
  try {
    const payload: Partial<ConnectionPayload> = { ...form };
    if (editing.value && !payload.password) delete payload.password;

    if (editing.value) {
      await connectionsService.update(editing.value.id, payload);
      toast.success(t('settings.updated'));
    } else {
      if (!payload.password) {
        toast.error(t('connections.password') + ': ' + t('common.genericError'));
        return;
      }
      await connectionsService.create(payload as ConnectionPayload);
      toast.success(t('settings.created'));
    }

    showModal.value = false;
    await load();
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function testRow(connection: DatabaseConnection): Promise<void> {
  try {
    await connectionsService.test(connection.id);
    toast.success(t('settings.tested'));
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

async function remove(connection: DatabaseConnection): Promise<void> {
  if (!confirm(t('settings.confirmRemove', { name: connection.name }))) return;

  try {
    await connectionsService.remove(connection.id);
    toast.success(t('settings.removed'));
    await load();
  } catch (e) {
    toast.error(apiErrorMessage(e));
  }
}

onMounted(load);
</script>

<template>
  <PageContainer>
    <PageHeader :title="t('settings.title')" :subtitle="t('settings.subtitle')" />

    <section class="rounded-xl border bg-card shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-border px-5 py-4">
        <div>
          <h2 class="text-lg font-semibold">{{ t('settings.connectionsTitle') }}</h2>
          <p class="mt-0.5 text-sm text-muted-foreground">{{ t('settings.connectionsSubtitle') }}</p>
        </div>
        <Button @click="openCreate">+ {{ t('settings.newConnection') }}</Button>
      </div>

      <p v-if="connections.length === 0" class="px-5 py-10 text-center text-sm text-muted-foreground">
        {{ t('settings.empty') }}
      </p>

      <Table v-else>
        <TableHeader>
          <TableRow>
            <TableHead>{{ t('connections.name') }}</TableHead>
            <TableHead>{{ t('connections.host') }}</TableHead>
            <TableHead>{{ t('connections.database') }}</TableHead>
            <TableHead>{{ t('connections.table') }}</TableHead>
            <TableHead class="text-right">{{ t('common.actions') }}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="connection in connections" :key="connection.id">
            <TableCell class="font-medium">{{ connection.name }}</TableCell>
            <TableCell>{{ connection.host }}:{{ connection.port }}</TableCell>
            <TableCell>{{ connection.database }}</TableCell>
            <TableCell>{{ connection.table_name }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-1">
                <Button variant="ghost" size="sm" @click="testRow(connection)">
                  {{ t('settings.testConnection') }}
                </Button>
                <Button variant="ghost" size="sm" @click="openEdit(connection)">
                  {{ t('common.edit') }}
                </Button>
                <Button variant="ghost" size="sm" class="text-destructive" @click="remove(connection)">
                  {{ t('common.delete') }}
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </section>

    <BaseModal
      v-if="showModal"
      :title="editing ? t('settings.editConnection') : t('settings.createConnection')"
      large
      @close="showModal = false"
    >
      <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
        <div class="sm:col-span-2">
          <Label for="conn-name">{{ t('connections.name') }}</Label>
          <Input id="conn-name" v-model="form.name" required />
        </div>
        <div>
          <Label for="conn-host">{{ t('connections.host') }}</Label>
          <Input id="conn-host" v-model="form.host" required />
        </div>
        <div>
          <Label for="conn-port">{{ t('connections.port') }}</Label>
          <Input id="conn-port" v-model.number="form.port" type="number" min="1" max="65535" required />
        </div>
        <div>
          <Label for="conn-database">{{ t('connections.database') }}</Label>
          <Input id="conn-database" v-model="form.database" required />
        </div>
        <div>
          <Label for="conn-username">{{ t('connections.username') }}</Label>
          <Input id="conn-username" v-model="form.username" required />
        </div>
        <div class="sm:col-span-2">
          <Label for="conn-password">{{ t('connections.password') }}</Label>
          <Input
            id="conn-password"
            v-model="form.password"
            type="password"
            :required="!editing"
            :placeholder="editing ? t('settings.passwordHint') : undefined"
          />
        </div>
        <div class="sm:col-span-2">
          <Label for="conn-table">{{ t('connections.table') }}</Label>
          <Input id="conn-table" v-model="form.table_name" :placeholder="t('connections.tablePlaceholder')" required />
        </div>
      </form>

      <template #footer>
        <Button variant="ghost" @click="showModal = false">{{ t('common.cancel') }}</Button>
        <Button variant="outline" @click="testCurrent">{{ t('settings.testConnection') }}</Button>
        <Button @click="save">{{ t('common.save') }}</Button>
      </template>
    </BaseModal>
  </PageContainer>
</template>
