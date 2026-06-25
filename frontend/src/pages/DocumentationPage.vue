<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Card, CardContent } from '@/components/ui/card'

const { t, tm } = useI18n()

const sections = ['install', 'admin', 'connection', 'module', 'callback'] as const

function sectionItems(key: (typeof sections)[number]): string[] {
  const items = tm(`documentation.sections.${key}.items`) as string[] | { [key: string]: string }
  if (Array.isArray(items)) return items
  return Object.values(items ?? {})
}
</script>

<template>
  <div class="mx-auto max-w-[900px] animate-page-in px-6 py-12 lg:py-16">
    <header class="mb-10">
      <h1 class="mb-3 text-3xl font-bold tracking-tight">{{ t('documentation.title') }}</h1>
      <p class="text-muted-foreground">{{ t('documentation.subtitle') }}</p>
    </header>

    <div class="space-y-5">
      <Card
        v-for="section in sections"
        :key="section"
        class="transition-shadow duration-200 hover:shadow-card-hover"
      >
        <CardContent class="p-6">
          <h2 class="mb-2 text-lg font-semibold tracking-tight">
            {{ t(`documentation.sections.${section}.title`) }}
          </h2>
          <p class="mb-4 text-sm leading-relaxed text-muted-foreground">
            {{ t(`documentation.sections.${section}.description`) }}
          </p>
          <ul class="list-disc space-y-2 pl-5 text-sm leading-relaxed">
            <li v-for="(item, index) in sectionItems(section)" :key="index">
              {{ item }}
            </li>
          </ul>
        </CardContent>
      </Card>

      <p class="text-sm text-muted-foreground">
        {{ t('documentation.footer') }}
      </p>
    </div>
  </div>
</template>
