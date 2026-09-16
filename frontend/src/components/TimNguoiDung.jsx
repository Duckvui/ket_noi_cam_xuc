import { useEffect, useRef, useState } from 'react'
import { requestData, thayDoiQuanHe } from '../services/nguoiDungService'
import UserAvatar from './UserAvatar'

export default function TimNguoiDung({ header = false }) {
  const [keyword, setKeyword] = useState('')
  const [results, setResults] = useState([])
  const [open, setOpen] = useState(false)
  const [loading, setLoading] = useState(false)
  const [busy, setBusy] = useState(null)
  const [error, setError] = useState('')
  const [revision, setRevision] = useState(0)
  const root = useRef(null)
  useEffect(() => {
    const close = event => { if (!root.current?.contains(event.target)) setOpen(false) }
    const refresh = () => setRevision(value => value + 1)
    document.addEventListener('pointerdown', close)
    window.addEventListener('quan-he-thay-doi', refresh)
    return () => { document.removeEventListener('pointerdown', close); window.removeEventListener('quan-he-thay-doi', refresh) }
  }, [])
  useEffect(() => {
    const controller = new AbortController()
    const timer = setTimeout(async () => {
      if (!keyword.trim()) { setResults([]); setLoading(false); return }
      setLoading(true); setError('')
      try {
        const body = await requestData(`/nguoi-dung/tim-kiem?q=${encodeURIComponent(keyword.trim())}`, { signal: controller.signal })
        if (!controller.signal.aborted) setResults(body.data)
      } catch (failure) { if (!controller.signal.aborted) setError(failure.message) }
      finally { if (!controller.signal.aborted) setLoading(false) }
    }, 400)
    return () => { clearTimeout(timer); controller.abort() }
  }, [keyword, revision])
  async function invite(account) {
    setBusy(account.id); setError('')
    try { const data = await thayDoiQuanHe(account.id, 'request'); setResults(current => current.map(item => item.id === account.id ? { ...item, ...data } : item)) }
    catch (failure) { setError(failure.message) }
    finally { setBusy(null) }
  }
  return <div ref={root} className={header ? 'header-search-wrap' : 'sidebar-search-wrap'}>
    <form className={header ? 'search' : 'friend-search'} onSubmit={event => { event.preventDefault(); setOpen(true); setRevision(value => value + 1) }}>
      <input aria-label="Tìm người dùng" maxLength={100} value={keyword} onFocus={() => setOpen(true)} onKeyDown={event => { if (event.key === 'Escape') setOpen(false) }} onChange={event => { setKeyword(event.target.value); setResults([]); setLoading(Boolean(event.target.value.trim())); setOpen(true) }} placeholder={header ? 'Tìm tên hoặc nick bạn bè...' : 'Tên hoặc nick tài khoản'} />
      <button type="submit" aria-label="Tìm">{header ? '⌕' : 'Tìm'}</button>
    </form>
    {open && keyword.trim() && <div className={header ? 'search-dropdown' : 'search-results'}>
      {loading && <p role="status">Đang tìm...</p>}
      {error && <p role="alert">{error}</p>}
      {!loading && !error && !results.length && <p>Không tìm thấy người dùng.</p>}
      {results.map(account => <div className="suggestion" key={account.id}>
        <a className="person-result" href={`#/profile/${account.id}`} onClick={() => setOpen(false)}><UserAvatar user={account} clickable={false} /><span><strong>{account.ten_hien_thi}</strong><small>@{account.tai_khoan}</small></span></a>
        {account.status === 'co_the_ket_ban' ? <button className="add-friend" disabled={busy !== null} onClick={() => invite(account)} aria-label={`Kết bạn với ${account.ten_hien_thi}`}>+</button> : <small>{account.status === 'ban_be' ? 'Bạn bè' : account.status === 'da_gui' ? 'Đã gửi' : 'Có lời mời'}</small>}
      </div>)}
    </div>}
  </div>
}
