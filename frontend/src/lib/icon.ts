import * as LucideIcons from '@lucide/vue';
import { Kanban, type LucideIcon } from '@lucide/vue';

export const DEFAULT_MODULE_ICON = 'kanban';

function toPascalCase(name: string): string {
  return name
    .split(/[-_]/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
    .join('');
}

/** Resolve a Lucide icon name (kebab-case) to its component. */
export function resolveLucideIcon(name?: string | null): LucideIcon {
  const raw = (name ?? '').trim();
  if (!raw) return Kanban;

  const icon = (LucideIcons as unknown as Record<string, LucideIcon>)[toPascalCase(raw)];
  return icon ?? Kanban;
}
