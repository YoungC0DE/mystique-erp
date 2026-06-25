<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { useThemeIcon } from '@/composables/useThemeIcon'
import { Button } from '@/components/ui/button'
import { Icon } from '@/components/ui/icon'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'

const { t } = useI18n()
const auth = useAuthStore()
const theme = useThemeStore()
const themeIcon = useThemeIcon()

const navLinkClass =
  'rounded-lg px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground [&.router-link-active]:bg-primary/10 [&.router-link-active]:text-primary'
</script>

<template>
  <div class="flex min-h-screen flex-col bg-background">
    <header
      class="sticky top-0 z-30 border-b border-border/80 bg-card/80 backdrop-blur-md supports-[backdrop-filter]:bg-card/70"
    >
      <div class="mx-auto flex h-16 max-w-[1280px] items-center justify-between gap-4 px-6">
        <RouterLink :to="{ name: 'home' }" class="flex items-center gap-3">
          <span
            class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-primary to-primary/80 text-sm font-bold text-primary-foreground shadow-sm"
          >
            M
          </span>
          <span class="text-[15px] font-semibold tracking-tight">Mystique CRM</span>
        </RouterLink>

        <nav class="hidden items-center gap-1 sm:flex">
          <RouterLink :to="{ name: 'home' }" :class="navLinkClass">
            {{ t('publicNav.home') }}
          </RouterLink>
          <RouterLink :to="{ name: 'documentation' }" :class="navLinkClass">
            {{ t('publicNav.documentation') }}
          </RouterLink>
        </nav>

        <div class="flex items-center gap-2">
          <Button
            variant="ghost"
            size="icon"
            class="text-muted-foreground"
            :title="theme.theme === 'dark' ? t('topbar.lightMode') : t('topbar.darkMode')"
            @click="theme.toggle"
          >
            <Icon :name="themeIcon" :size="20" />
          </Button>

          <template v-if="auth.isAuthenticated">
            <Button as-child variant="default" size="sm">
              <RouterLink :to="{ name: 'dashboard' }">{{ t('publicNav.goToApp') }}</RouterLink>
            </Button>
          </template>
          <template v-else>
            <Button as-child variant="ghost" size="sm">
              <RouterLink :to="{ name: 'login' }">{{ t('publicNav.signIn') }}</RouterLink>
            </Button>
            <Button as-child variant="default" size="sm">
              <RouterLink :to="{ name: 'register' }">{{ t('publicNav.register') }}</RouterLink>
            </Button>
          </template>
        </div>
      </div>
    </header>

    <main class="flex-1">
      <RouterView v-slot="{ Component }">
        <Transition name="page" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>

    <footer class="border-t border-border/80 bg-card py-8">
      <p class="text-center text-sm text-muted-foreground">
        {{ t('publicNav.footer') }}
      </p>
    </footer>
  </div>
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.page-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.page-leave-to {
  opacity: 0;
}
</style>
