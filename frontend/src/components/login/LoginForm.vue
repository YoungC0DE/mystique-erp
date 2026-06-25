<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Icon } from '@/components/ui/icon';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { controlClass } from '@/lib/inputStyles';

const email = defineModel<string>('email', { required: true });
const password = defineModel<string>('password', { required: true });

const props = defineProps<{
  error?: string;
  loading?: boolean;
}>();

defineEmits<{
  submit: [];
}>();

const { t } = useI18n();

const hasError = computed(() => Boolean(props.error));

const fieldClass = (filled: boolean) =>
  cn(
    controlClass,
    'h-11 pl-10',
    filled && 'border-primary/35 bg-primary/[0.02]',
    hasError.value && 'border-destructive/50 focus-visible:border-destructive/60 focus-visible:ring-destructive/15',
  );
</script>

<template>
  <div class="mx-auto w-full max-w-[400px]">
    <header class="mb-10">
      <span
        class="mb-5 grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-primary to-primary/75 text-lg font-bold text-primary-foreground shadow-md shadow-primary/20"
      >
        M
      </span>
      <h1 class="text-2xl font-bold tracking-tight text-foreground">
        {{ t('login.title') }}
      </h1>
      <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
        {{ t('login.subtitle') }}
      </p>
    </header>

    <form class="space-y-5" @submit.prevent="$emit('submit')">
      <div class="space-y-2">
        <Label for="email">{{ t('login.email') }}</Label>
        <div class="relative">
          <Icon
            name="mail"
            :size="18"
            class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground"
          />
          <Input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            :placeholder="t('login.emailPlaceholder')"
            :class="fieldClass(Boolean(email))"
            :disabled="loading"
          />
        </div>
      </div>

      <div class="space-y-2">
        <div class="flex items-center justify-between gap-3">
          <Label for="password">{{ t('login.password') }}</Label>
          <button
            type="button"
            class="text-xs font-medium text-primary transition-colors hover:text-primary/80"
            :title="t('login.forgotPasswordHint')"
          >
            {{ t('login.forgotPassword') }}
          </button>
        </div>
        <div class="relative">
          <Icon
            name="lock-keyhole"
            :size="18"
            class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground"
          />
          <Input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            :placeholder="t('login.passwordPlaceholder')"
            :class="fieldClass(Boolean(password))"
            :disabled="loading"
          />
        </div>
      </div>

      <p
        v-if="error"
        class="flex items-start gap-2 rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-2.5 text-sm text-destructive"
        role="alert"
      >
        <Icon name="circle-alert" :size="16" class="mt-0.5 shrink-0" />
        <span>{{ error }}</span>
      </p>

      <Button
        class="h-11 w-full text-[15px] font-semibold shadow-md shadow-primary/20 transition-all hover:shadow-lg hover:shadow-primary/25"
        type="submit"
        :disabled="loading"
      >
        <Spinner v-if="loading" class="mr-2 border-primary-foreground/30 border-t-primary-foreground" />
        {{ loading ? t('login.submitting') : t('login.submit') }}
      </Button>
    </form>
  </div>
</template>
