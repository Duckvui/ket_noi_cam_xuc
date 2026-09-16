import { yeuCau } from './DichVuCamXuc'

export const layDanhMucAvatar = () => yeuCau('/avatar-animation')
export const luuAvatar = (MaAvatarAnimation) => yeuCau('/avatar-animation/me', { method: 'PUT', body: JSON.stringify({ MaAvatarAnimation }) })
