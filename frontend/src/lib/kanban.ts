/**
 * Cores flat dos status do Kanban (slug → hex).
 * Slugs legados mantidos para compatibilidade durante migração.
 */
export const KANBAN_STATUS_ACCENT: Record<string, string> = {
  inputar: '#94a3b8',
  em_andamento: '#f59e0b',
  aprovados: '#22c55e',
  reprovados: '#ef4444',
  backlog: '#94a3b8',
  processando: '#f59e0b',
  finalizado: '#22c55e',
  reprovado: '#ef4444',
};

export function kanbanStatusAccent(slug: string, fallback?: string): string {
  return KANBAN_STATUS_ACCENT[slug] ?? fallback ?? 'var(--primary)';
}
