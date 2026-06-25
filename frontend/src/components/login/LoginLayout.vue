<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useThemeIcon } from '@/composables/useThemeIcon';
import { Button } from '@/components/ui/button';
import { Icon } from '@/components/ui/icon';
import { useThemeStore } from '@/stores/theme';
import LoginHero from '@/components/login/LoginHero.vue';

const { t } = useI18n();
const theme = useThemeStore();
const themeIcon = useThemeIcon();
</script>

<template>
  <div class="flex min-h-screen bg-background">
    <section class="relative flex w-full flex-col md:w-[55%] lg:w-[40%]">
      <div class="absolute right-4 top-4 z-10 md:right-6 md:top-6">
        <Button
          variant="ghost"
          size="icon"
          class="text-muted-foreground hover:text-foreground"
          :title="theme.theme === 'dark' ? t('topbar.lightMode') : t('topbar.darkMode')"
          @click="theme.toggle"
        >
          <Icon :name="themeIcon" :size="20" />
        </Button>
      </div>

      <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-10 lg:px-14 xl:px-16">
        <RouterView />
      </div>
    </section>

    <aside class="relative hidden overflow-hidden md:flex md:w-[45%] lg:w-[60%]" aria-hidden="true">
      <LoginHero />
    </aside>
  </div>
</template>
