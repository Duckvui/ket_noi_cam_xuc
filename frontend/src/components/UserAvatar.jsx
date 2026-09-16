import AvatarAnimation from './AvatarAnimation'

export default function UserAvatar({ user, size, showEmotion = false, clickable = true, className = '' }) {
  const id = user?.id ?? user?.idTaiKhoan
  const avatar = <AvatarAnimation idTaiKhoan={id} avatarUrl={user?.avatar} hienTen={showEmotion} className={className} />
  const style = size ? { width: size, height: size, display: 'inline-flex', flexShrink: 0 } : undefined
  return clickable && id ? <a className="user-avatar-link" style={style} href={`#/profile/${id}`} aria-label={`Trang cá nhân ${user?.ten_hien_thi || user?.name || ''}`}>{avatar}</a> : <span className="user-avatar-link" style={style}>{avatar}</span>
}
