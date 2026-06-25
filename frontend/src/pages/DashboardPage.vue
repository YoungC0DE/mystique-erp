<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import StatCard from '@/components/layout/StatCard.vue'
import ModuleCard from '@/components/layout/ModuleCard.vue'
import EmptyState from '@/components/layout/EmptyState.vue'
import { useAuthStore } from '@/stores/auth'
import { useModulesStore } from '@/stores/modules'

const { t } = useI18n()
const auth = useAuthStore()
const modules = useModulesStore()

const profileLabel = computed(() =>
  auth.isAdmin ? t('topbar.admin') : (auth.user?.roles?.[0]?.name ?? t('dashboard.user')),
)
</script>

<template>
  <PageContainer>
    <PageHeader :title="t('nav.dashboard')" />

    <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-4">
      <StatCard
        :label="t('dashboard.availableModules')"
        :value="modules.allowed.length"
        icon="layout-grid"
      />
      <StatCard
        :label="t('dashboard.permissions')"
        :value="auth.isAdmin ? '∞' : auth.permissions.length"
        icon="shield"
      />
      <StatCard
        :label="t('dashboard.profile')"
        :value="profileLabel"
        icon="user"
      />
    </div>

    <div class="mt-8">
      <h2 class="mb-4 text-base font-semibold tracking-tight">{{ t('dashboard.quickAccess') }}</h2>
      <div
        v-if="modules.allowed.length"
        class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-4"
      >
        <ModuleCard
          v-for="mod in modules.allowed"
          :key="mod.id"
          :name="mod.name"
          :icon="mod.icon ?? undefined"
          :description="t('dashboard.fieldsCount', { count: mod.fields_count ?? 0 })"
          :to="{ name: 'module', params: { slug: mod.slug } }"
        />
      </div>
      <EmptyState
        v-else
        icon="layout-grid"
        :title="t('dashboard.noModules')"
      />
    </div>
  </PageContainer>
</template>
