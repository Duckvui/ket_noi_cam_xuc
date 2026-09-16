import UserAvatar from '../UserAvatar'
function WallSidebar({ user, onLogout, friendCount = 0 }) {
  return <aside className="left-rail"><div className="profile-mini"><UserAvatar user={user} className="big" showEmotion /><div><a href={`#/profile/${user.id}`}><strong>{user.ten_hien_thi}</strong></a><small>@{user.tai_khoan.split('@')[0]}</small></div></div><a className="side-active" href="#home">⌂ <span>Trang chủ</span></a><a>◉ <span>Khám phá</span></a><a href="#danh-sach-ban-be">♧ <span>Bạn bè</span><b>{friendCount}</b></a><a>✉ <span>Tin nhắn</span></a><a>♡ <span>Đã lưu</span></a><hr /><a>⚙ <span>Cài đặt</span></a><button className="logout" onClick={onLogout}>Đăng xuất</button></aside>
}
export default WallSidebar
