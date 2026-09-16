import { useEffect, useRef, useState } from 'react'
import UserAvatar from '../components/UserAvatar'
import { mediaUrl, thayDoiQuanHe } from '../services/nguoiDungService'

export default function ProfilePage({ profile, onBack, onStartChat, onUpdated }) {
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [confirmBlock, setConfirmBlock] = useState(false)
  const [coverFailed, setCoverFailed] = useState(false)
  const mounted = useRef(true)
  useEffect(() => { mounted.current = true; return () => { mounted.current = false } }, [])
  async function action(type) {
    if (busy) return
    setBusy(true); setError('')
    try { const updated = await thayDoiQuanHe(profile.id, type); if (mounted.current) { onUpdated(updated); setConfirmBlock(false) } }
    catch (failure) { setError(failure.message) }
    finally { setBusy(false) }
  }
  async function chat() {
    setBusy(true); setError('')
    try { await onStartChat(profile.id) }
    catch (failure) { setError(failure.message) }
    finally { setBusy(false) }
  }
  return <section className="profile-page">
    <button className="profile-back" onClick={onBack}>← Quay lại trang chủ</button>
    <div className="profile-page-cover">{profile.anh_bia && !coverFailed && <img src={profile.anh_bia} alt="Ảnh bìa" onError={() => setCoverFailed(true)} />}</div>
    <UserAvatar user={profile} className="profile-page-avatar" showEmotion clickable={false} />
    <h1>{profile.ten_hien_thi}</h1><p className="profile-nick">@{profile.tai_khoan}</p><p>{profile.gioi_thieu || 'Chưa có phần giới thiệu.'}</p>
    {!profile.is_self && <div className="profile-page-actions">
      {!profile.bi_chan && <>
        {profile.status === 'ban_be' ? <><span>✓ Bạn bè</span><button disabled={busy} onClick={() => action('unfriend')}>Hủy kết bạn</button></> : profile.status === 'da_gui' ? <><span>Đã gửi lời mời</span><button disabled={busy} onClick={() => action('cancel')}>Hủy lời mời</button></> : Array.isArray(profile.status) ? <><button disabled={busy} onClick={() => action('accept')}>Chấp nhận</button><button disabled={busy} onClick={() => action('reject')}>Từ chối</button></> : <button disabled={busy} onClick={() => action('request')}>＋ Kết bạn</button>}
        <button disabled={busy} onClick={chat}>✉ Nhắn tin</button>
        <button disabled={busy} onClick={() => action(profile.dang_theo_doi ? 'unfollow' : 'follow')}>{profile.dang_theo_doi ? 'Đang theo dõi · Bỏ theo dõi' : 'Theo dõi'}</button>
      </>}
      <button disabled={busy} onClick={() => profile.da_chan ? action('unblock') : setConfirmBlock(true)}>{profile.da_chan ? 'Bỏ chặn' : 'Chặn'}</button>
    </div>}
    {confirmBlock && <div role="alertdialog" aria-label="Xác nhận chặn"><p>Bạn có chắc muốn chặn người dùng này?</p><button disabled={busy} onClick={() => action('block')}>Chặn người dùng</button><button disabled={busy} onClick={() => setConfirmBlock(false)}>Hủy</button></div>}
    {busy && <p role="status">Đang cập nhật...</p>}{error && <p className="error" role="alert">{error}</p>}
    <h2>Bài viết công khai</h2>{profile.bi_chan ? <p>Nội dung bị hạn chế do quan hệ chặn.</p> : profile.posts.length ? profile.posts.map(post => <article className="profile-post" key={post.id}><small>{new Date(post.published_at).toLocaleString('vi-VN')}</small><p>{post.content}</p>{post.image_url && <img src={mediaUrl(post.image_url)} alt="Ảnh bài viết" />}</article>) : <p className="feed-message">Chưa có bài viết công khai.</p>}
  </section>
}
