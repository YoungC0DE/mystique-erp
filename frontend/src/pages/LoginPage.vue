<script setup lang="ts">

import { ref } from 'vue'

import { useRoute, useRouter } from 'vue-router'

import { useI18n } from 'vue-i18n'

import { Button } from '@/components/ui/button'

import { Card, CardContent } from '@/components/ui/card'

import { Input } from '@/components/ui/input'

import { Label } from '@/components/ui/label'
import { useAuthStore } from '@/stores/auth'

import { apiErrorMessage } from '@/services/http'



const { t } = useI18n()

const auth = useAuthStore()

const router = useRouter()

const route = useRoute()



const email = ref('')

const password = ref('')

const error = ref('')



async function submit(): Promise<void> {

  error.value = ''

  try {

    await auth.login(email.value, password.value)

    const redirect = (route.query.redirect as string) || '/dashboard'

    router.push(redirect)

  } catch (e) {

    error.value = apiErrorMessage(e, t('login.error'))

  }

}

</script>



<template>

  <div class="grid min-h-[calc(100vh-4rem)] place-items-center px-5 py-12">

    <Card class="w-full max-w-[420px] shadow-card-hover">

      <CardContent class="px-8 py-9">

        <div class="mb-8 text-center">

          <span

            class="mx-auto mb-4 grid h-12 w-12 place-items-center rounded-xl bg-gradient-to-br from-primary to-primary/80 text-lg font-bold text-primary-foreground shadow-sm"

          >

            M

          </span>

          <h1 class="mb-1.5 text-xl font-semibold tracking-tight">Mystique CRM</h1>

          <p class="text-sm text-muted-foreground">{{ t('login.subtitle') }}</p>

        </div>



        <form class="space-y-4" @submit.prevent="submit">

          <div class="space-y-2">

            <Label for="email">{{ t('login.email') }}</Label>

            <Input

              id="email"

              v-model="email"

              type="email"

              required

              autocomplete="email"

              placeholder="voce@empresa.com"

            />

          </div>

          <div class="space-y-2">

            <Label for="password">{{ t('login.password') }}</Label>

            <Input

              id="password"

              v-model="password"

              type="password"

              required

              autocomplete="current-password"

              placeholder="••••••••"

            />

          </div>



          <p v-if="error" class="rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-2 text-sm text-destructive">

            {{ error }}

          </p>



          <Button class="mt-2 h-11 w-full" type="submit">
            {{ t('login.submit') }}
          </Button>

        </form>



        <p class="mt-6 text-center text-sm text-muted-foreground">

          {{ t('login.noAccount') }}

          <RouterLink class="font-medium text-primary hover:underline" :to="{ name: 'register' }">

            {{ t('login.registerLink') }}

          </RouterLink>

        </p>

      </CardContent>

    </Card>

  </div>

</template>

