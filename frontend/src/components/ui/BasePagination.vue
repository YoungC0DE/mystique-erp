<script setup lang="ts">
import { Button } from '@/components/ui/button';

import { Icon } from '@/components/ui/icon';
const props = defineProps<{
  currentPage: number;

  lastPage: number;

  total?: number;
}>();

const emit = defineEmits<{ (e: 'change', page: number): void }>();
function go(page: number): void {
  if (page < 1 || page > props.lastPage || page === props.currentPage) return;

  emit('change', page);
}
</script>
<template>
  <div
    v-if="lastPage > 1 || total"
    data-testid="pagination"
    class="mt-5 flex items-center gap-3 rounded-xl border border-border/60 bg-card px-4 py-3 shadow-card"
  >
    <span v-if="total !== undefined" class="text-sm text-muted-foreground">{{ total }} registro(s)</span>

    <div class="flex-1" />

    <div class="flex items-center gap-1.5">
      <Button variant="outline" size="sm" class="gap-1" :disabled="currentPage <= 1" @click="go(currentPage - 1)">
        <Icon name="chevron-left" :size="16" />

        Anterior
      </Button>

      <span class="min-w-[64px] rounded-md bg-muted/60 px-2.5 py-1 text-center text-sm font-medium text-foreground">
        {{ currentPage }} / {{ lastPage }}
      </span>

      <Button
        variant="outline"
        size="sm"
        class="gap-1"
        :disabled="currentPage >= lastPage"
        @click="go(currentPage + 1)"
      >
        Próximo

        <Icon name="chevron-right" :size="16" />
      </Button>
    </div>
  </div>
</template>
