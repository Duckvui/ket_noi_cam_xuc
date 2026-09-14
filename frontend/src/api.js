const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '/api').replace(/\/$/, '')

export function api(path, options = {}) {
  return fetch(`${apiBaseUrl}${path}`, {
    credentials: 'include',
    ...options,
  })
}
