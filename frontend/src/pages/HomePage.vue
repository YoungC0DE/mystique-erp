<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Icon } from '@/components/ui/icon'

const { t } = useI18n()

const flowSteps = [
  { key: 'install', icon: 'download' },
  { key: 'connection', icon: 'database' },
  { key: 'module', icon: 'puzzle' },
  { key: 'board', icon: 'kanban' },
  { key: 'callback', icon: 'refresh-cw' },
] as const
</script>

<template>
  <div class="relative overflow-hidden">
    <div
      class="pointer-events-none absolute inset-x-0 top-0 h-[480px] bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,hsl(var(--primary)/0.12),transparent)]"
      aria-hidden="true"
    />

    <div class="relative mx-auto max-w-[1280px] px-6 py-16 lg:py-20">
      <section class="mb-20 text-center">
        <p
          class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-primary"
        >
          <Icon name="sparkles" :size="14" />
          {{ t('home.badge') }}
        </p>
        <h1 class="mx-auto mb-6 max-w-[720px] text-4xl font-bold tracking-tight sm:text-[3.25rem] sm:leading-tight">
          {{ t('home.title') }}
        </h1>
        <p class="mx-auto mb-10 max-w-[580px] text-lg leading-relaxed text-muted-foreground">
          {{ t('home.subtitle') }}
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3">
          <Button as-child size="lg" class="h-11 px-6">
            <RouterLink :to="{ name: 'documentation' }">{{ t('home.ctaDocs') }}</RouterLink>
          </Button>
          <Button as-child variant="outline" size="lg" class="h-11 px-6">
            <RouterLink :to="{ name: 'login' }">{{ t('home.ctaSignIn') }}</RouterLink>
          </Button>
        </div>
      </section>

      <section class="mb-20">
        <h2 class="mb-10 text-center text-2xl font-semibold tracking-tight">{{ t('home.flowTitle') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card
            v-for="(step, index) in flowSteps"
            :key="step.key"
            class="group relative transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card-hover"
          >
            <CardContent class="p-5">
              <span
                class="mb-4 grid h-10 w-10 place-items-center rounded-xl bg-primary/10 text-primary transition-colors group-hover:bg-primary/15"
              >
                <Icon :name="step.icon" :size="20" />
              </span>
              <h3 class="mb-2 font-semibold tracking-tight">{{ t(`home.flow.${step.key}.title`) }}</h3>
              <p class="text-sm leading-relaxed text-muted-foreground">
                {{ t(`home.flow.${step.key}.description`) }}
              </p>
              <span
                v-if="index < flowSteps.length - 1"
                class="absolute -right-2 top-1/2 hidden -translate-y-1/2 text-muted-foreground/40 lg:block"
                aria-hidden="true"
              >
                →
              </span>
            </CardContent>
          </Card>
        </div>
      </section>

      <section class="grid gap-5 md:grid-cols-3">
        <Card
          v-for="feature in ['oss', 'integration', 'kanban']"
          :key="feature"
          class="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card-hover"
        >
          <CardContent class="p-6">
            <h3 class="mb-2 font-semibold tracking-tight">{{ t(`home.features.${feature}.title`) }}</h3>
            <p class="text-sm leading-relaxed text-muted-foreground">
              {{ t(`home.features.${feature}.description`) }}
            </p>
          </CardContent>
        </Card>
      </section>
    </div>
  </div>
</template>
