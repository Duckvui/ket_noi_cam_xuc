import { useCallback, useEffect, useRef, useState } from 'react'
import { chamSocPet, doiPet, guiLoiMoiPet, layLoiMoiPet, layPet, layPetChiTiet, traLoiMoiPet } from '../services/DichVuPet'
import { echo } from '../realtime'
import PetAnimation from './PetAnimation'
import LoiMoiNuoiPet from './LoiMoiNuoiPet'
import LichSuTuongTacPet from './LichSuTuongTacPet'
import './PetChung.css'

const tenTrangThai = { rat_buon: 'Rất buồn', buon: 'Buồn', binh_thuong: 'Bình thường', vui: 'Vui', rat_vui: 'Rất vui' }

function ThePet({ pet, danhMuc, hanhDong, reload }) {
  const [busy, setBusy] = useState(false)
  const [loi, setLoi] = useState('')
  const [lichSu, setLichSu] = useState(null)
  const [ten, setTen] = useState(pet.TenPet)
  const [loai, setLoai] = useState(pet.LoaiPet)
  const [remaining, setRemaining] = useState(0)

  useEffect(() => {
    if (!remaining) return
    const timer = setInterval(() => setRemaining((value) => Math.max(0, value - 1)), 1000)
    return () => clearInterval(timer)
  }, [remaining])
  const layLichSu = useCallback(async () => {
    try { setLichSu((await layPetChiTiet(pet.idPet)).lich_su); setLoi('') }
    catch (error) { setLoi(error.message) }
  }, [pet.idPet])
  const hienLichSu = lichSu !== null
  useEffect(() => { if (hienLichSu) Promise.resolve().then(layLichSu) }, [pet.NgayCapNhat, layLichSu, hienLichSu])
  async function thucHien(operation, chamSoc = false) {
    if (busy) return
    setBusy(true); setLoi('')
    try { await operation(); if (chamSoc) { setRemaining(10) } await reload() }
    catch (error) { setLoi(error.message) }
    finally { setBusy(false) }
  }

  return <article className="the-pet"><div className="pet-tom-tat"><PetAnimation pet={pet} /><div><h3>{pet.TenPet}</h3><p className="pet-streak">🔥 Chuỗi {pet.current_streak || 0} ngày · Kỷ lục {pet.longest_streak || 0} ngày</p>{pet.last_activity_date && !pet.current_streak && <small>Chuỗi đã bị đứt. Hãy tương tác để bắt đầu lại.</small>}<small>{pet.thanh_vien.map((m) => m.ten).join(' & ')}</small><p>{tenTrangThai[pet.TrangThaiPet]} · {pet.DiemCamXuc}/100</p><meter min="0" max="100" value={pet.DiemCamXuc} aria-label="Điểm cảm xúc" /></div></div><div className="hanh-dong-pet">{hanhDong.map((action) => <button disabled={busy || remaining > 0} key={action.ma} onClick={() => thucHien(() => chamSocPet(pet.idPet, action.ma), true)}>{action.ten}</button>)}</div>{remaining > 0 && <small>Chờ {remaining} giây để chăm sóc tiếp</small>}<details><summary>Đổi tên / loại pet</summary><form className="form-pet" onSubmit={(e) => { e.preventDefault(); thucHien(() => doiPet(pet.idPet, { TenPet: ten, LoaiPet: loai })) }}><label>Tên mới<input required maxLength={50} value={ten} onChange={(e) => setTen(e.target.value)} /></label><label>Loại pet<select value={loai} onChange={(e) => setLoai(e.target.value)}>{danhMuc.map((type) => <option key={type.ma} value={type.ma}>{type.ten}</option>)}</select></label><button disabled={busy}>Lưu pet</button></form></details><button onClick={layLichSu}>Xem lịch sử</button>{lichSu && <LichSuTuongTacPet lichSu={lichSu} thanhVien={pet.thanh_vien} />}{loi && <p role="alert">{loi}</p>}{busy && <p role="status">Đang lưu…</p>}</article>
}

export default function PetChung({ user }) {
  const [pets, setPets] = useState([])
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [loi, setLoi] = useState('')
  const [notice, setNotice] = useState('')
  const requestId = useRef(0)
  const reload = useCallback(async () => {
    if (document.hidden) return
    const id = ++requestId.current
    try { const [p, d] = await Promise.all([layPet(), layLoiMoiPet()]); if (id === requestId.current) { setPets(p); setData(d); setLoi('') } }
    catch (error) { if (id === requestId.current) setLoi(error.message) }
    finally { if (id === requestId.current) setLoading(false) }
  }, [])
  useEffect(() => {
    Promise.resolve().then(reload)
    const timer = setInterval(reload, 15000)
    document.addEventListener('visibilitychange', reload)
    window.addEventListener('pet-hoat-dong', reload)
    return () => { requestId.current += 1; clearInterval(timer); document.removeEventListener('visibilitychange', reload); window.removeEventListener('pet-hoat-dong', reload) }
  }, [reload])
  const petIds = pets.map((pet) => pet.idPet).join(',')
  useEffect(() => {
    if (!echo || !petIds) return
    const ids = petIds.split(',')
    ids.forEach((id) => echo.private(`pet.${id}`).listen('.pet.thay-doi', reload))
    return () => ids.forEach((id) => echo.leave(`pet.${id}`))
  }, [petIds, reload])
  async function xuLy(operation, message) {
    if (busy) return
    setBusy(true); setNotice(''); setLoi('')
    try { await operation(); await reload(); setNotice(message) }
    catch (error) { setLoi(error.message) }
    finally { setBusy(false) }
  }
  return <section className="pet-chung"><h2>Pet cảm xúc chung</h2><p className="pet-note">Một người bạn, một pet chung. Cảm xúc bạn chọn sẽ được ghi vào lịch sử pet để hai người cùng chăm sóc.</p>{loading && <p role="status">Đang tải pet…</p>}{loi && <p role="alert">{loi} <button onClick={reload}>Thử lại</button></p>}{data && <LoiMoiNuoiPet user={user} data={data} busy={busy} onGui={(form) => xuLy(() => guiLoiMoiPet(form), 'Đã gửi lời mời.')} onTraLoi={(id, action) => xuLy(() => traLoiMoiPet(id, action), 'Đã xử lý lời mời.')} />}{notice && <p role="status">{notice}</p>}{!loading && !pets.length && <p>Chưa có pet chung. Hãy mời một người bạn.</p>}{pets.map((pet) => <ThePet key={pet.idPet} pet={pet} danhMuc={data?.loai_pet || []} hanhDong={data?.hanh_dong || []} reload={reload} />)}</section>
}
