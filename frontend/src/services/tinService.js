import { api } from '../api'

async function getResponseData(response, defaultMessage) {
  const result = await response.json().catch(() => null)

  if (!response.ok) throw new Error(Object.values(result?.errors || {}).flat()[0] || result?.message || defaultMessage)

  return result?.data
}

export async function layDanhSachTin() {
  const response = await api('/tins', { headers: { Accept: 'application/json' } })
  return getResponseData(response, 'Không thể lấy danh sách tin')
}

export async function xemTin(idTin) {
  const response = await api(`/tins/${idTin}/xem`, { method: 'POST', headers: { Accept: 'application/json' } })
  return getResponseData(response, 'Không thể mở tin')
}

export async function thaCamXucTin(idTin, type) {
  const response = await api(`/tins/${idTin}/cam-xuc`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ type }),
  })
  return getResponseData(response, 'Không thể thả cảm xúc')
}

export async function traLoiTin(idTin, noiDung) {
  const response = await api(`/tins/${idTin}/tra-loi`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ NoiDung: noiDung }),
  })
  return getResponseData(response, 'Không thể gửi tin nhắn')
}

export async function dangTin({ loaiTin, noiDung, cheDoHienThi, media, mauNen = 'purple' }) {
  const data = new FormData()
  data.append('LoaiTin', loaiTin)
  data.append('NoiDung', noiDung || '')
  data.append('CheDoHienThi', cheDoHienThi)
  data.append('MauNen', mauNen)
  if (media) data.append('media', media)

  const response = await api('/tins', { method: 'POST', headers: { Accept: 'application/json' }, body: data })
  return getResponseData(response, 'Không thể đăng tin')
}
