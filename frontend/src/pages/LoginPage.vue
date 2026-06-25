<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import LoginForm from '@/components/login/LoginForm.vue';
import { useAuthStore } from '@/stores/auth';
import { apiErrorMessage } from '@/services/http';

const { t } = useI18n();
const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function submit(): Promise<void> {
  error.value = '';
  loading.value = true;
  try {
    await auth.login(email.value, password.value);
    const redirect = (route.query.redirect as string) || '/dashboard';
    router.push(redirect);
  } catch (e) {
    error.value = apiErrorMessage(e, t('login.error'));
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <LoginForm v-model:email="email" v-model:password="password" :error="error" :loading="loading" @submit="submit" />
</template>
