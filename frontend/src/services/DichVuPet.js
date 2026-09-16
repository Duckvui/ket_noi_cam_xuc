import { yeuCau } from './DichVuCamXuc'

export const layPet = () => yeuCau('/pet-chung')
export const layPetChiTiet = (id) => yeuCau(`/pet-chung/${id}`)
export const layLoiMoiPet = () => yeuCau('/loi-moi-nuoi-pet')
export const guiLoiMoiPet = (data) => yeuCau('/loi-moi-nuoi-pet', { method: 'POST', body: JSON.stringify(data) })
export const traLoiMoiPet = (id, action) => yeuCau(`/loi-moi-nuoi-pet/${id}`, { method: 'PATCH', body: JSON.stringify({ action }) })
export const chamSocPet = (id, MaHanhDong) => yeuCau(`/pet-chung/${id}/tuong-tac`, { method: 'POST', body: JSON.stringify({ MaHanhDong }) })
export const doiPet = (id, data) => yeuCau(`/pet-chung/${id}`, { method: 'PATCH', body: JSON.stringify(data) })
