import { useCallback, useEffect, useState } from 'react'
import UserAvatar from '../UserAvatar'
import TimNguoiDung from '../TimNguoiDung'
import { requestData, thayDoiQuanHe } from '../../services/nguoiDungService'

export default function RightSidebar({ onFriendCountChange }) {
  const [data, setData] = useState({ requests: [], suggestions: [], friends: [] })
  const [busyId, setBusyId] = useState(null)
  const [notice, setNotice] = useState('')
  const [loading, setLoading] = useState(true)
  const load = useCallback(async () => {
    try {
      const body = await requestData('/ket-ban')
      setData(body.data); onFriendCountChange(body.data.friend_count)
    } catch (failure) { setNotice(failure.message) }
    finally { setLoading(false) }
  }, [onFriendCountChange])
  useEffect(() => {
    void Promise.resolve().then(load)
    const timer = setInterval(load, 15000)
    window.addEventListener('quan-he-thay-doi', load)
    return () => { clearInterval(timer); window.removeEventListener('quan-he-thay-doi', load) }
  }, [load])
  async function action(account, type) {
    setBusyId(account.id); setNotice('')
    try { await thayDoiQuanHe(account.id, type); await load(); setNotice(type === 'request' ? 'Đã gửi lời mời.' : 'Đã cập nhật kết nối.') }
    catch (failure) { setNotice(failure.message) }
    finally { setBusyId(null) }
  }
  const person = account => <a className="person-result" href={`#/profile/${account.id}`}><UserAvatar user={account} clickable={false} /><span><strong>{account.ten_hien_thi}</strong><small>@{account.tai_khoan}</small></span></a>
  return <aside className="right-rail">
    <section><div className="section-title"><h3>Xu hướng</h3></div><p className="trend">#motngaybinhyen</p><p className="trend">#petyeuthuong</p><p className="trend">#chamsocbanthan</p></section>
    {loading && <p role="status">Đang tải kết nối...</p>}
    {notice && <p className="connection-notice" role="status">{notice}</p>}
    <section className="connections"><h3>Lời mời kết bạn</h3>{!data.requests.length && <small>Chưa có lời mời mới.</small>}{data.requests.map(invite => <div className="suggestion" key={invite.idKetBan}>{person(invite.nguoi_gui)}<div className="connection-actions"><button disabled={busyId !== null} onClick={() => action(invite.nguoi_gui, 'accept')}>Chấp nhận</button><button disabled={busyId !== null} onClick={() => action(invite.nguoi_gui, 'reject')}>Từ chối</button></div></div>)}</section>
    <section className="connections"><h3>Tìm bạn bè</h3><TimNguoiDung /></section>
    <section className="connections" id="danh-sach-ban-be"><h3>Bạn bè</h3>{!data.friends?.length && <small>Chưa có bạn bè.</small>}{data.friends?.map(account => <div className="suggestion" key={account.id}>{person(account)}</div>)}</section>
    <section className="connections"><h3>Gợi ý kết nối</h3>{!data.suggestions.length && <small>Chưa có gợi ý mới.</small>}{data.suggestions.map(account => <div className="suggestion" key={account.id}>{person(account)}<button className="add-friend" disabled={busyId !== null} onClick={() => action(account, 'request')} aria-label={`Kết bạn với ${account.ten_hien_thi}`}>+</button></div>)}</section>
  </aside>
}