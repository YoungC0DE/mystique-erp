<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Icon } from '@/components/ui/icon';

const { t } = useI18n();

const features = [
  { key: 'modules', icon: 'layout-grid' },
  { key: 'users', icon: 'users' },
  { key: 'permissions', icon: 'shield' },
  { key: 'reports', icon: 'bar-chart-3' },
] as const;

const kanbanColumns = [
  { key: 'input', tone: 'bg-primary/15 text-primary' },
  { key: 'progress', tone: 'bg-violet-500/15 text-violet-600 dark:text-violet-300' },
  { key: 'done', tone: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300' },
] as const;
</script>

<template>
  <div
    class="relative flex h-full w-full flex-col justify-center overflow-hidden bg-gradient-to-br from-primary via-[hsl(262_68%_48%)] to-[hsl(270_55%_28%)] px-8 py-12 lg:px-12 xl:px-16"
  >
    <div
      class="pointer-events-none absolute inset-0 opacity-[0.07]"
      style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px"
    />
    <div class="pointer-events-none absolute -left-24 top-16 h-72 w-72 rounded-full bg-white/10 blur-3xl" />
    <div class="pointer-events-none absolute -right-16 bottom-12 h-80 w-80 rounded-full bg-violet-300/20 blur-3xl" />
    <div
      class="pointer-events-none absolute left-1/3 top-1/2 h-56 w-56 -translate-y-1/2 rounded-full bg-fuchsia-400/10 blur-2xl"
    />

    <div class="relative z-10 mx-auto w-full max-w-[560px]">
      <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-primary-foreground/70">
        {{ t('login.hero.eyebrow') }}
      </p>
      <h2 class="mb-3 max-w-md text-3xl font-bold leading-tight tracking-tight text-primary-foreground lg:text-4xl">
        {{ t('login.hero.title') }}
      </h2>
      <p class="mb-10 max-w-sm text-sm leading-relaxed text-primary-foreground/75 lg:text-[15px]">
        {{ t('login.hero.subtitle') }}
      </p>

      <div class="relative">
        <div
          class="absolute -left-3 top-8 z-20 hidden animate-[float_6s_ease-in-out_infinite] rounded-2xl border border-white/20 bg-white/10 p-3.5 shadow-xl backdrop-blur-md lg:block"
        >
          <div class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/15 text-primary-foreground">
              <Icon name="users" :size="18" />
            </span>
            <div>
              <p class="text-xs font-medium text-primary-foreground/70">{{ t('login.hero.users') }}</p>
              <p class="text-sm font-semibold text-primary-foreground">12 {{ t('login.hero.active') }}</p>
            </div>
          </div>
        </div>

        <div
          class="absolute -right-2 top-20 z-20 hidden animate-[float_7s_ease-in-out_infinite_1s] rounded-2xl border border-white/20 bg-white/10 p-3.5 shadow-xl backdrop-blur-md lg:block"
        >
          <div class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/15 text-primary-foreground">
              <Icon name="shield" :size="18" />
            </span>
            <div>
              <p class="text-xs font-medium text-primary-foreground/70">{{ t('login.hero.permissions') }}</p>
              <p class="text-sm font-semibold text-primary-foreground">RBAC</p>
            </div>
          </div>
        </div>

        <div
          class="relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-5 shadow-2xl backdrop-blur-xl"
        >
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <p class="text-sm font-semibold text-primary-foreground">{{ t('login.hero.dashboard') }}</p>
              <p class="text-xs text-primary-foreground/65">{{ t('login.hero.dashboardHint') }}</p>
            </div>
            <span
              class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[11px] font-medium text-primary-foreground/80"
            >
              {{ t('login.hero.live') }}
            </span>
          </div>

          <div class="mb-4 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            <div
              v-for="feature in features"
              :key="feature.key"
              class="rounded-xl border border-white/15 bg-white/10 p-3"
            >
              <Icon :name="feature.icon" :size="16" class="mb-2 text-primary-foreground/80" />
              <p class="text-[11px] font-medium text-primary-foreground/70">
                {{ t(`login.hero.${feature.key}`) }}
              </p>
            </div>
          </div>

          <div class="rounded-xl border border-white/15 bg-white/5 p-3">
            <p class="mb-2.5 text-xs font-semibold text-primary-foreground/80">
              {{ t('login.hero.kanban') }}
            </p>
            <div class="grid grid-cols-3 gap-2">
              <div
                v-for="column in kanbanColumns"
                :key="column.key"
                class="rounded-lg border border-white/10 bg-white/5 p-2"
              >
                <p class="mb-2 text-[10px] font-medium text-primary-foreground/65">
                  {{ t(`login.hero.columns.${column.key}`) }}
                </p>
                <div class="space-y-1.5">
                  <div
                    v-for="n in 2"
                    :key="n"
                    class="h-7 rounded-md border border-white/10 bg-white/10"
                    :class="n === 1 ? column.tone : 'bg-white/5'"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          class="absolute -bottom-4 left-8 z-20 hidden animate-[float_8s_ease-in-out_infinite_0.5s] rounded-2xl border border-white/20 bg-white/10 px-4 py-3 shadow-xl backdrop-blur-md md:block"
        >
          <div class="flex items-center gap-2">
            <Icon name="bar-chart-3" :size="16" class="text-primary-foreground/80" />
            <span class="text-xs font-medium text-primary-foreground/85">{{ t('login.hero.reports') }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes float {
  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-8px);
  }
}
</style>
