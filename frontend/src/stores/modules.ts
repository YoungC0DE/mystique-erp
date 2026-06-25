import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Module } from '@/types';
import { modulesService } from '@/services/modules.service';

export const useModulesStore = defineStore('modules', () => {
  const allowed = ref<Module[]>([]);
  const loaded = ref(false);

  async function loadAllowed(force = false): Promise<void> {
    if (loaded.value && !force) return;
    allowed.value = await modulesService.allowed();
    loaded.value = true;
  }

  function findBySlug(slug: string): Module | undefined {
    return allowed.value.find((m) => m.slug === slug);
  }

  function reset(): void {
    allowed.value = [];
    loaded.value = false;
  }

  return { allowed, loaded, loadAllowed, findBySlug, reset };
});
