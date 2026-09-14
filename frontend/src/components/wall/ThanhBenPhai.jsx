import { useCallback, useEffect, useState } from 'react'
import { api } from '../../api'

function RightSidebar({ onFriendCountChange, queryFromHeader, onOpenProfile }) {
  const [requests, setRequests] = useState([])
  const [suggestions, setSuggestions] = useState([])
  const [busyId, setBusyId] = useState(null)
  const [notice, setNotice] = useState('')
  const [keyword, setKeyword] = useState('')
  const [results, setResults] = useState([])
  const [searching, setSearching] = useState(false)
  const [profile, setProfile] = useState(null)

  const loadConnections = useCallback(async () => {
    try {
      const response = await api('/ket-ban', { headers: { Accept: 'application/json' } })
      if (!response.ok) throw new Error()
      const body = await response.json()
      setRequests(body.data.requests || [])
      setSuggestions(body.data.suggestions || [])
      onFriendCountChange(body.data.friend_count || 0)
    } catch { setNotice('Không tải được danh sách kết nối.') }
  }, [onFriendCountChange])

  useEffect(() => { Promise.resolve().then(loadConnections) }, [loadConnections])

  async function sendRequest(account) {
    setBusyId(account.id); setNotice('')
    try {
      const response = await api('/ket-ban', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ idTaiKhoan: account.id }) })
      const body = await response.json().catch(() => ({}))
      if (!response.ok) throw new Error(Object.values(body.errors || {}).flat()[0] || body.message || 'Không thể gửi lời mời.')
      setSuggestions((current) => current.filter((item) => item.id !== account.id))
      setNotice(`Đã gửi lời mời tới ${account.ten_hien_thi}.`)
    } catch (error) { setNotice(error.message) } finally { setBusyId(null) }
  }

  async function respond(invite, action) {
    setBusyId(invite.idKetBan); setNotice('')
    try {
      const response = await api(`/ket-ban/${invite.idKetBan}`, { method: 'PATCH', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ action }) })
      const body = await response.json().catch(() => ({}))
      if (!response.ok) throw new Error(body.message || 'Không thể xử lý lời mời.')
      setRequests((current) => current.filter((item) => item.idKetBan !== invite.idKetBan))
      if (action === 'chap_nhan') onFriendCountChange((current) => current + 1)
      setNotice(body.message)
    } catch (error) { setNotice(error.message) } finally { setBusyId(null) }
  }

  async function search(event) {
    event.preventDefault()
    if (keyword.trim().length < 2) return setNotice('Nhập ít nhất 2 ký tự để tìm người dùng.')
    setSearching(true); setNotice('')
    try {
      const response = await api(`/ket-ban/tim-kiem?q=${encodeURIComponent(keyword.trim())}`, { headers: { Accept: 'application/json' } })
      const body = await response.json().catch(() => ({}))
      if (!response.ok) throw new Error(Object.values(body.errors || {}).flat()[0] || 'Không thể tìm người dùng.')
      setResults(body.data || [])
    } catch (error) { setNotice(error.message) } finally { setSearching(false) }
  }

  useEffect(() => {
    if (!queryFromHeader || queryFromHeader.length < 2) return
    api(`/ket-ban/tim-kiem?q=${encodeURIComponent(queryFromHeader)}`, { headers: { Accept: 'application/json' } })
      .then((response) => response.ok ? response.json() : null)
      .then((body) => { setResults(body?.data || []); if (body?.data?.length === 1) openProfile(body.data[0]) })
      .catch(() => setNotice('Không thể tìm người dùng.'))
  }, [queryFromHeader])

  async function openProfile(account) {
    setNotice('')
    try {
      const response = await api(`/nguoi-dung/${account.id}`, { headers: { Accept: 'application/json' } })
      const body = await response.json().catch(() => ({}))
      if (!response.ok) throw new Error(body.message || 'Không tải được trang cá nhân.')
      setProfile(body.data)
      onOpenProfile(body.data)
    } catch (error) { setNotice(error.message) }
  }

  async function followProfile() {
    const response = await api(`/nguoi-dung/${profile.id}/theo-doi`, { method: 'POST', headers: { Accept: 'application/json' } })
    if (response.ok) setProfile((current) => ({ ...current, dang_theo_doi: true }))
  }

  return <aside className="right-rail"><section><div className="section-title"><h3>Xu hướng</h3><button>•••</button></div><p className="trend">#motngaybinhyen <span>1,2K bài viết</span></p><p className="trend">#petyeuthuong <span>986 bài viết</span></p><p className="trend">#chamsocbanthan <span>745 bài viết</span></p><a className="see-more">Xem thêm</a></section>
    <section className="connections"><div className="section-title"><h3>Lời mời kết bạn</h3></div>{requests.length === 0 && <small>Chưa có lời mời mới.</small>}{requests.map((invite) => <div className="suggestion" key={invite.idKetBan}><div className="avatar">💜</div><div><strong>{invite.nguoi_gui.ten_hien_thi}</strong><small>@{invite.nguoi_gui.tai_khoan.split('@')[0]}</small><div className="connection-actions"><button disabled={busyId === invite.idKetBan} onClick={() => respond(invite, 'chap_nhan')}>Chấp nhận</button><button disabled={busyId === invite.idKetBan} onClick={() => respond(invite, 'tu_choi')}>Xóa</button></div></div></div>)}</section>
    <section className="connections"><div className="section-title"><h3>Tìm bạn bè</h3></div><form className="friend-search" onSubmit={search}><input value={keyword} onChange={(event) => setKeyword(event.target.value)} placeholder="Tên hoặc nick tài khoản" /><button disabled={searching}>{searching ? '...' : 'Tìm'}</button></form>{results.map((account) => <div className="suggestion" key={account.id}><button className="person-result" onClick={() => openProfile(account)}><div className="avatar">💜</div><div><strong>{account.ten_hien_thi}</strong><small>@{account.tai_khoan.split('@')[0]}</small></div></button>{account.status === 'co_the_ket_ban' ? <button className="add-friend" disabled={busyId === account.id} onClick={() => sendRequest(account)} aria-label={`Kết bạn với ${account.ten_hien_thi}`}>+</button> : <small className="friend-status">{account.status === 'ban_be' ? 'Bạn bè' : account.status === 'da_gui' ? 'Đã gửi' : 'Có lời mời'}</small>}</div>)}{keyword && !searching && results.length === 0 && <small>Không tìm thấy người dùng phù hợp.</small>}</section>
    <section className="connections"><div className="section-title"><h3>Gợi ý kết nối</h3></div>{suggestions.length === 0 && <small>Chưa có gợi ý mới.</small>}{suggestions.map((account) => <div className="suggestion" key={account.id}><div className="avatar">💜</div><div><strong>{account.ten_hien_thi}</strong><small>@{account.tai_khoan.split('@')[0]}</small></div><button className="add-friend" disabled={busyId === account.id} onClick={() => sendRequest(account)} aria-label={`Kết bạn với ${account.ten_hien_thi}`}>+</button></div>)}{notice && <p className="connection-notice" role="status">{notice}</p>}</section>
    {profile && !onOpenProfile && <dialog className="profile-dialog" open><button className="close-profile" onClick={() => setProfile(null)}>✕</button><div className="profile-cover"></div><div className="profile-avatar">💜</div><h2>{profile.ten_hien_thi}</h2><small>@{profile.tai_khoan.split('@')[0]}</small><p>{profile.gioi_thieu || 'Chưa có phần giới thiệu.'}</p><div className="profile-actions">{profile.la_ban ? <span>Bạn bè</span> : <button onClick={() => sendRequest(profile)}>Kết bạn</button>}<button onClick={followProfile} disabled={profile.dang_theo_doi}>{profile.dang_theo_doi ? 'Đang theo dõi' : 'Theo dõi'}</button><button className="block-button" onClick={() => { setNotice(`Đã ẩn ${profile.ten_hien_thi} khỏi danh sách gợi ý.`); setProfile(null) }}>Chặn</button></div><h3>Bài viết công khai</h3>{profile.posts.length ? profile.posts.map((post) => <article className="mini-post" key={post.id}><small>{new Date(post.published_at).toLocaleString('vi-VN')}</small><p>{post.content || 'Bài viết có ảnh.'}</p></article>) : <p className="connection-notice">Chưa có bài viết công khai.</p>}</dialog>}
  </aside>
}

export default RightSidebar
