export default function BoChonCamXuc({ danhMuc, giaTri, onChange, disabled }) {
  return <div className="lua-chon-cam-xuc" role="group" aria-label="Cảm xúc hiện tại">{danhMuc.map((item) => <button key={item.idCamXuc} disabled={disabled} aria-pressed={giaTri === item.idCamXuc} onClick={() => onChange(item.idCamXuc)}><span>{item.icon}</span> {item.ten}</button>)}</div>
}
