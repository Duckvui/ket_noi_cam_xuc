import { api } from '../api'

export async function requestData(path, options = {}) {
  const response = await api(path, { ...options, headers: { Accept: 'application/json', ...options.headers } })
  const body = await response.json().catch(() => null)
  if (!response.ok) throw new Error(Object.values(body?.errors || {}).flat()[0] || body?.message || 'Không kết nối được máy chủ.')
  return body
}
export async function thayDoiQuanHe(id, action) {
  const body = await requestData(`/nguoi-dung/${id}/quan-he`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action }) })
  window.dispatchEvent(new Event('quan-he-thay-doi'))
  return body.data
}
export function mediaUrl(path) {
  if (!path || !path.startsWith('/api/')) return path || ''
  return `${(import.meta.env.VITE_API_BASE_URL || '/api').replace(/\/$/, '')}${path.slice(4)}`
}
