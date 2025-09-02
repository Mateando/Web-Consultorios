export const STATUS_COLORS = {
  programada: { bg: '#3b82f6', text: '#ffffff' },
  confirmada: { bg: '#10b981', text: '#ffffff' },
  en_curso: { bg: '#f59e0b', text: '#1f2937' },
  completada: { bg: '#6b7280', text: '#ffffff' },
  cancelada: { bg: '#ef4444', text: '#ffffff' },
  no_asistio: { bg: '#9ca3af', text: '#111827' }
}

export const getStatusColor = (status) => STATUS_COLORS[status] || { bg: '#ffffff', text: '#111827' }
