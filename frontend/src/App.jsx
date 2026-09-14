import { useEffect, useState } from 'react'
import { api } from './api'
import TrangDangNhap from './pages/TrangDangNhap'
import TrangTuong from './pages/TrangTuong'
import './App.css'

const initialForm = { tai_khoan: '', mat_khau: '' }

function App() {
  const [form, setForm] = useState(initialForm)
  const [user, setUser] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => { api('/auth/me').then((response) => (response.ok ? response.json() : null)).then((payload) => payload?.data && setUser(payload.data)).catch(() => {}) }, [])

  async function login(event) {
    event.preventDefault(); setLoading(true); setError('')
    try {
      const response = await api('/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(form) })
      const payload = await response.json()
      if (!response.ok) return setError(payload.message || 'Không thể đăng nhập. Vui lòng thử lại.')
      setUser(payload.data); setForm(initialForm)
    } catch { setError('Không kết nối được máy chủ. Hãy kiểm tra Laravel đang chạy tại cổng 8000.') } finally { setLoading(false) }
  }

  async function logout() { await api('/auth/logout', { method: 'POST' }); setUser(null) }

  return user ? <TrangTuong user={user} onLogout={logout} /> : <TrangDangNhap form={form} setForm={setForm} error={error} loading={loading} onSubmit={login} />
}

export default App
