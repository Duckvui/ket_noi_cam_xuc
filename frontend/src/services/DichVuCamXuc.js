import { api } from '../api'

export async function yeuCau(path, options = {}) {
  const response = await api(path, { ...options, headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...options.headers } })
  const body = await response.json().catch(() => ({}))
  if (!response.ok) throw new Error(Object.values(body.errors || {}).flat()[0] || body.message || 'Không thể tải dữ liệu. Vui lòng thử lại.')
  return body.data
}

export const layDanhMucCamXuc = () => yeuCau('/cam-xuc-ca-nhan/danh-muc')
export const layTrangThai = (ids) => yeuCau(`/cam-xuc-ca-nhan?${ids.map((id) => `ids[]=${encodeURIComponent(id)}`).join('&')}`)
export const luuCamXuc = (idCamXuc, CheDoHienThi) => yeuCau('/cam-xuc-ca-nhan/me', { method: 'PUT', body: JSON.stringify({ idCamXuc, CheDoHienThi }) })
