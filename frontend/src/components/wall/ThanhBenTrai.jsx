function WallSidebar({ user, onLogout, friendCount = 0 }) {
  return <aside className="left-rail"><div className="profile-mini"><div className="avatar big">💜</div><div><strong>{user.ten_hien_thi}</strong><small>@{user.tai_khoan.split('@')[0]}</small></div></div><a className="side-active">⌂ <span>Trang chủ</span></a><a>◉ <span>Khám phá</span></a><a>♧ <span>Bạn bè</span><b>{friendCount}</b></a><a>✉ <span>Tin nhắn</span></a><a>♡ <span>Đã lưu</span></a><hr /><a>⚙ <span>Cài đặt</span></a><button className="logout" onClick={onLogout}>Đăng xuất</button></aside>
}
export default WallSidebar
