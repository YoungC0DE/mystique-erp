<script setup lang="ts">
import { reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuthStore } from '@/stores/auth'
import { profileService } from '@/services/profile.service'
import { apiErrorMessage } from '@/services/http'
import { useToast } from '@/composables/useToast'
import { setLocale, SUPPORTED_LOCALES, type AppLocale } from '@/i18n'
import { controlClass } from '@/lib/inputStyles'

const { t } = useI18n()
const auth = useAuthStore()
const toast = useToast()

const localeLabels: Record<AppLocale, string> = {
  'pt-BR': 'Português (Brasil)',
  en: 'English',
}

const account = reactive({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  locale: (auth.user?.locale ?? 'pt-BR') as AppLocale,
})

const passwords = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

function onLocaleChange(): void {
  setLocale(account.locale)
}

async function saveAccount(): Promise<void> {
  try {
    const user = await profileService.update({
      name: account.name,
      email: account.email,
      locale: account.locale,
    })
    auth.updateUser(user)
    toast.success(t('profile.saved'))
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}

async function savePassword(): Promise<void> {
  if (passwords.password !== passwords.password_confirmation) {
    toast.error(t('profile.passwordMismatch'))
    return
  }
  try {
    await profileService.updatePassword({ ...passwords })
    passwords.current_password = ''
    passwords.password = ''
    passwords.password_confirmation = ''
    toast.success(t('profile.passwordChanged'))
  } catch (e) {
    toast.error(apiErrorMessage(e))
  }
}
</script>

<template>
  <PageContainer>
    <PageHeader :title="t('profile.title')" :subtitle="t('profile.subtitle')" />

    <div class="grid grid-cols-[repeat(auto-fit,minmax(320px,1fr))] gap-5">
      <Card>
        <CardContent class="p-6">
          <h3 class="mb-5 text-base font-semibold tracking-tight">{{ t('profile.accountSection') }}</h3>
          <form class="space-y-3.5" @submit.prevent="saveAccount">
            <div class="space-y-1.5">
              <Label>{{ t('profile.name') }}</Label>
              <Input v-model="account.name" />
            </div>
            <div class="space-y-1.5">
              <Label>{{ t('profile.email') }}</Label>
              <Input v-model="account.email" type="email" />
            </div>
            <div class="space-y-1.5">
              <Label>{{ t('profile.language') }}</Label>
              <select v-model="account.locale" :class="controlClass" @change="onLocaleChange">
                <option v-for="loc in SUPPORTED_LOCALES" :key="loc" :value="loc">
                  {{ localeLabels[loc] }}
                </option>
              </select>
            </div>
            <Button type="submit" :disabled="!account.name">{{ t('profile.save') }}</Button>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-6">
          <h3 class="mb-5 text-base font-semibold tracking-tight">{{ t('profile.passwordSection') }}</h3>
          <form class="space-y-3.5" @submit.prevent="savePassword">
            <div class="space-y-1.5">
              <Label>{{ t('profile.currentPassword') }}</Label>
              <Input v-model="passwords.current_password" type="password" autocomplete="current-password" />
            </div>
            <div class="space-y-1.5">
              <Label>{{ t('profile.newPassword') }}</Label>
              <Input v-model="passwords.password" type="password" autocomplete="new-password" />
            </div>
            <div class="space-y-1.5">
              <Label>{{ t('profile.confirmPassword') }}</Label>
              <Input v-model="passwords.password_confirmation" type="password" autocomplete="new-password" />
            </div>
            <Button
              type="submit"
              :disabled="!passwords.current_password || !passwords.password"
            >
              {{ t('profile.changePassword') }}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </PageContainer>
</template>
