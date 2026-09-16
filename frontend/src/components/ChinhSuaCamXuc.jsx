import { useContext, useEffect, useState } from 'react'
import { NguCanhCamXuc } from './NguCanhCamXucContext'
import { layDanhMucAvatar, luuAvatar } from '../services/DichVuAvatar'
import { layDanhMucCamXuc, luuCamXuc } from '../services/DichVuCamXuc'
import BoChonAvatar from './BoChonAvatar'
import BoChonCamXuc from './BoChonCamXuc'

export default function ChinhSuaCamXuc({ user }) {
  const { duLieu, capNhat, loi: loiTai, taiLai } = useContext(NguCanhCamXuc)
  const data = duLieu[user.id]
  const [avatars, setAvatars] = useState([])
  const [moods, setMoods] = useState([])
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [loi, setLoi] = useState('')
  const [notice, setNotice] = useState('')
  const [cheDoMoi, setCheDo] = useState(null)
  const cheDo = cheDoMoi ?? data?.cam_xuc?.che_do ?? 'Chi_Minh_Toi'
  async function taiDanhMuc() {
    setLoading(true); setLoi('')
    try { const [a, m] = await Promise.all([layDanhMucAvatar(), layDanhMucCamXuc()]); setAvatars(a); setMoods(m) }
    catch (error) { setLoi(error.message) }
    finally { setLoading(false) }
  }
  useEffect(() => { Promise.resolve().then(taiDanhMuc) }, [])
  async function luu(operation) {
    if (busy) return
    setBusy(true); setLoi(''); setNotice('')
    try { capNhat(await operation()); setNotice('Đã lưu thay đổi.') }
    catch (error) { setLoi(error.message) }
    finally { setBusy(false) }
  }
  return <details className="chinh-sua-cam-xuc"><summary>Chỉnh sửa avatar & cảm xúc cá nhân</summary>{loading ? <p role="status">Đang tải lựa chọn…</p> : <><BoChonAvatar danhMuc={avatars} giaTri={data?.ma_avatar} disabled={busy || !data} onChange={(ma) => luu(() => luuAvatar(ma))} /><label>Ai được xem cảm xúc?<select value={cheDo} disabled={busy} onChange={(e) => { const value = e.target.value; setCheDo(value); if (data?.cam_xuc) luu(() => luuCamXuc(data.cam_xuc.idCamXuc, value)) }}><option value="Chi_Minh_Toi">Chỉ mình tôi</option><option value="Ban_Be">Bạn bè</option></select></label><BoChonCamXuc danhMuc={moods} giaTri={data?.cam_xuc?.idCamXuc} disabled={busy || !data} onChange={(id) => luu(() => luuCamXuc(id, cheDo))} />{!moods.length && <p>Chưa có cảm xúc khả dụng.</p>}{data?.cam_xuc && <p>{data.cam_xuc.ten} · {new Date(data.cam_xuc.thoi_gian).toLocaleString('vi-VN')}</p>}</>}{(loi || loiTai) && <p role="alert">{loi || loiTai} <button onClick={() => { taiDanhMuc(); taiLai() }}>Thử lại</button></p>}{(busy || notice) && <p role="status">{busy ? 'Đang lưu…' : notice}</p>}</details>
}
