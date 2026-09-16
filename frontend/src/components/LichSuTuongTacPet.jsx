export default function LichSuTuongTacPet({ lichSu, thanhVien }) {
  return <div className="lich-su-pet"><h4>Lịch sử gần đây</h4>{!lichSu.length && <p>Chưa có tương tác.</p>}{lichSu.map((item) => <p key={item.id}><strong>{thanhVien.find((user) => user.id === Number(item.idTaiKhoan))?.ten || 'Thành viên'}</strong> · {item.noi_dung} · {item.diem > 0 ? '+' : ''}{item.diem} điểm <small>{new Date(item.thoi_gian.replace(' ', 'T') + (item.thoi_gian.includes('Z') ? '' : 'Z')).toLocaleString('vi-VN')}</small></p>)}</div>
}
