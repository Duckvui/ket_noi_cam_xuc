import AvatarAnimation from './AvatarAnimation'

export default function BoChonAvatar({ danhMuc, giaTri, onChange, disabled }) {
  return <div className="lua-chon-avatar" role="group" aria-label="Chọn avatar"><button disabled={disabled} aria-pressed={!giaTri} onClick={() => onChange(null)}>Mặc định</button>{danhMuc.map((item) => <button key={item.ma} title={item.ten} disabled={disabled} aria-pressed={giaTri === item.ma} onClick={() => onChange(item.ma)}><AvatarAnimation ma={item.ma} /><span>{item.ten}</span></button>)}</div>
}
