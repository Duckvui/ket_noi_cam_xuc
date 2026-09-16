import { useEffect, useState } from 'react'
import { api } from './api'
import TrangDangNhap from './pages/TrangDangNhap'
import TrangDangKy from './pages/TrangDangKy'
import TrangTuong from './pages/TrangTuong'
import './App.css'
import { NhaCungCapCamXuc } from './components/NguCanhCamXuc'

const initialForm = { tai_khoan: '', mat_khau: '' }

function App() {
  const [form, setForm] = useState(initialForm)
  const [user, setUser] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [registrationMessage, setRegistrationMessage] = useState('')
  const [authPage, setAuthPage] = useState(() => window.location.hash === '#dang-ky' ? 'register' : 'login')

  useEffect(() => {
    const syncPage = () => { setAuthPage(window.location.hash === '#dang-ky' ? 'register' : 'login'); setError('') }
    window.addEventListener('hashchange', syncPage)
    return () => window.removeEventListener('hashchange', syncPage)
  }, [])

  useEffect(() => { api('/auth/me').then((response) => (response.ok ? response.json() : null)).then((payload) => payload?.data && setUser(payload.data)).catch(() => {}) }, [])

  async function login(event) {
    event.preventDefault(); setLoading(true); setError(''); setRegistrationMessage('')
    try {
      const response = await api('/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(form) })
      const payload = await response.json()
      if (!response.ok) return setError(payload.message || 'Không thể đăng nhập. Vui lòng thử lại.')
      setUser(payload.data); setForm(initialForm)
    } catch { setError('Không kết nối được máy chủ. Hãy kiểm tra Laravel đang chạy tại cổng 8000.') } finally { setLoading(false) }
  }

  async function logout() { await api('/auth/logout', { method: 'POST' }); setUser(null) }

  function registered(account) {
    setForm({ tai_khoan: account.tai_khoan, mat_khau: '' })
    setError('')
    setRegistrationMessage('Đăng ký thành công! Nhập mật khẩu để đăng nhập.')
    setAuthPage('login')
    window.location.hash = 'dang-nhap'
  }

  return user ? <NhaCungCapCamXuc key={user.id}><TrangTuong user={user} onLogout={logout} /></NhaCungCapCamXuc> : authPage === 'register' ? <TrangDangKy onLogin={() => { window.location.hash = 'dang-nhap' }} onRegistered={registered} /> : <TrangDangNhap form={form} setForm={setForm} error={error} loading={loading} onSubmit={login} success={registrationMessage} />
}

export default App
