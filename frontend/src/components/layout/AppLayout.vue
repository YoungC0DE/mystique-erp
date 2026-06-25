<script setup lang="ts">

import { computed, onMounted, ref } from 'vue'

import { useRouter } from 'vue-router'

import { useI18n } from 'vue-i18n'

import { Button } from '@/components/ui/button'

import {

  DropdownMenu,

  DropdownMenuContent,

  DropdownMenuItem,

  DropdownMenuSeparator,

  DropdownMenuTrigger,

} from '@/components/ui/dropdown-menu'

import { Icon } from '@/components/ui/icon'

import { cn } from '@/lib/utils'

import { useAuthStore } from '@/stores/auth'

import { useThemeStore } from '@/stores/theme'

import { useModulesStore } from '@/stores/modules'

import { useThemeIcon } from '@/composables/useThemeIcon'

import { initEcho } from '@/services/echo'



const { t } = useI18n()

const auth = useAuthStore()

const theme = useThemeStore()

const themeIcon = useThemeIcon()

const modules = useModulesStore()

const router = useRouter()



const sidebarOpen = ref(false)



const navLinkClass =

  'group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium text-sidebar-foreground/75 transition-all duration-200 hover:bg-sidebar-accent hover:text-sidebar-foreground [&.router-link-active]:bg-sidebar-accent [&.router-link-active]:font-semibold [&.router-link-active]:text-sidebar-accent-foreground [&.router-link-active]:shadow-sm before:absolute before:left-0 before:top-1/2 before:h-5 before:w-[3px] before:-translate-y-1/2 before:rounded-r-full before:bg-sidebar-primary before:opacity-0 before:transition-opacity [&.router-link-active]:before:opacity-100'



const adminLinks = computed(() =>

  [

    { name: 'dashboard', label: t('nav.dashboard'), icon: 'layout-dashboard', show: true },

    { name: 'modules', label: t('nav.modules'), icon: 'layout-grid', show: auth.can('read') },

    { name: 'users', label: t('nav.users'), icon: 'users', show: auth.can('read') },

    { name: 'roles', label: t('nav.rolesPermissions'), icon: 'shield', show: auth.can('read') },

    { name: 'reports', label: t('nav.reports'), icon: 'bar-chart', show: auth.can('read') },

  ].filter((l) => l.show),

)



const initials = computed(() => {

  const name = auth.user?.name ?? '?'

  return name

    .split(' ')

    .map((p) => p[0])

    .slice(0, 2)

    .join('')

    .toUpperCase()

})



async function handleLogout(): Promise<void> {

  await auth.logout()

  modules.reset()

  router.push({ name: 'home' })

}



onMounted(async () => {

  await modules.loadAllowed()

  initEcho()

})

</script>



<template>

  <div class="flex min-h-screen bg-background">

    <aside

      :class="

        cn(

          'sticky top-0 flex h-screen w-[260px] shrink-0 flex-col border-r border-sidebar-border bg-sidebar',

          'max-[860px]:fixed max-[860px]:z-40 max-[860px]:shadow-overlay max-[860px]:transition-transform max-[860px]:duration-300',

          sidebarOpen ? 'max-[860px]:translate-x-0' : 'max-[860px]:-translate-x-full',

        )

      "

    >

      <div class="flex items-center gap-3 border-sidebar-border px-5 py-5">

        <span

          class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-primary to-primary/80 text-sm font-bold text-primary-foreground shadow-sm"

        >

          M

        </span>

        <div class="min-w-0">

          <span class="block truncate text-[15px] font-semibold tracking-tight text-sidebar-foreground">Mystique CRM</span>
          <span class="block text-[11px] text-sidebar-muted">Ferramenta Interna - Aquisição</span>

        </div>

      </div>



      <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-3 py-4">

        <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-sidebar-muted">

          {{ t('nav.management') }}

        </p>

        <RouterLink

          v-for="link in adminLinks"

          :key="link.name"

          :to="{ name: link.name }"

          :class="navLinkClass"

          @click="sidebarOpen = false"

        >

          <Icon

            :name="link.icon"

            :size="18"

            class="w-[18px] shrink-0 opacity-70 transition-opacity group-hover:opacity-100 [.router-link-active_&]:opacity-100"

          />

          <span>{{ link.label }}</span>

        </RouterLink>



        <p class="mb-2 mt-6 px-3 text-[11px] font-semibold uppercase tracking-widest text-sidebar-muted">

          {{ t('nav.modules') }}

        </p>

        <RouterLink
          v-for="mod in modules.allowed"
          :key="mod.id"
          :to="{ name: 'module', params: { slug: mod.slug } }"
          :class="navLinkClass"
          @click="sidebarOpen = false"
        >

            <Icon

              :name="mod.icon ?? ''"

              :size="18"

              class="w-[18px] shrink-0 opacity-70 transition-opacity group-hover:opacity-100 [.router-link-active_&]:opacity-100"

            />

            <span class="truncate">{{ mod.name }}</span>

          </RouterLink>

          <p v-if="modules.allowed.length === 0" class="px-3 py-2 text-[13px] text-sidebar-muted">

            {{ t('nav.noModules') }}

          </p>

      </nav>

    </aside>



    <div

      v-if="sidebarOpen"

      class="fixed inset-0 z-30 hidden bg-black/40 backdrop-blur-[1px] max-[860px]:block"

      @click="sidebarOpen = false"

    />



    <div class="flex min-w-0 flex-1 flex-col">

      <header

        class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-border/80 bg-card/80 px-5 backdrop-blur-md supports-[backdrop-filter]:bg-card/70"

      >

        <Button

          variant="ghost"

          size="icon"

          class="hidden max-[860px]:inline-flex"

          @click="sidebarOpen = !sidebarOpen"

        >

          <Icon name="menu" :size="20" />

        </Button>

        <div class="flex-1" />

        <Button

          variant="ghost"

          size="icon"

          class="text-muted-foreground hover:text-foreground"

          :title="theme.theme === 'dark' ? t('topbar.lightMode') : t('topbar.darkMode')"

          @click="theme.toggle"

        >

          <Icon :name="themeIcon" :size="20" />

        </Button>

        <DropdownMenu>

          <DropdownMenuTrigger as-child>

            <button

              type="button"

              class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-border/60 bg-background px-2 py-1.5 text-left transition-all duration-200 hover:border-primary/25 hover:bg-accent/50"

              :aria-label="t('userMenu.openMenu')"

            >

              <span

                class="grid h-8 w-8 place-items-center rounded-lg bg-primary/10 text-xs font-semibold text-primary"

              >

                {{ initials }}

              </span>

              <div class="hidden min-w-0 flex-col leading-tight min-[861px]:flex">

                <strong class="truncate text-sm font-semibold">{{ auth.user?.name }}</strong>

                <small class="truncate text-xs text-muted-foreground">

                  {{ auth.isAdmin ? t('topbar.admin') : (auth.user?.roles?.[0]?.name ?? '—') }}

                </small>

              </div>

              <Icon name="chevron-down" :size="16" class="hidden text-muted-foreground min-[861px]:block" />

            </button>

          </DropdownMenuTrigger>

          <DropdownMenuContent align="end" class="w-48">

            <DropdownMenuItem v-if="auth.isAdmin" @select="router.push({ name: 'settings' })">

              <Icon name="settings" :size="16" class="mr-2 opacity-60" />

              {{ t('userMenu.settings') }}

            </DropdownMenuItem>

            <DropdownMenuItem @select="router.push({ name: 'profile' })">

              <Icon name="user" :size="16" class="mr-2 opacity-60" />

              {{ t('userMenu.profile') }}

            </DropdownMenuItem>

            <DropdownMenuSeparator />

            <DropdownMenuItem class="text-destructive focus:text-destructive" @select="handleLogout">

              <Icon name="log-out" :size="16" class="mr-2 opacity-60" />

              {{ t('userMenu.logout') }}

            </DropdownMenuItem>

          </DropdownMenuContent>

        </DropdownMenu>

      </header>



      <main class="flex-1">

        <RouterView v-slot="{ Component }">

          <Transition name="page" mode="out-in">

            <component :is="Component" />

          </Transition>

        </RouterView>

      </main>

    </div>

  </div>

</template>



<style scoped>

.page-enter-active,

.page-leave-active {

  transition: opacity 0.2s ease, transform 0.2s ease;

}



.page-enter-from {

  opacity: 0;

  transform: translateY(6px);

}



.page-leave-to {

  opacity: 0;

  transform: translateY(-4px);

}

</style>

