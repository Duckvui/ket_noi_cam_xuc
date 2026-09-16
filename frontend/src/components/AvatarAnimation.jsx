import { useContext, useEffect, useState } from 'react'
import { NguCanhCamXuc } from './NguCanhCamXucContext'
import './AvatarAnimation.css'

const mau = { meo: '#edb6d1', gau: '#d6b395', tho: '#d8ccf3', cao: '#f4b773' }

export default function AvatarAnimation({ idTaiKhoan, ma, camXuc, className = '', hienTen = false }) {
  const context = useContext(NguCanhCamXuc)
  const dangKy = context?.dangKy
  const [anhLoi, setAnhLoi] = useState(null)
  useEffect(() => dangKy?.(idTaiKhoan), [dangKy, idTaiKhoan])
  const data = context?.duLieu[idTaiKhoan]
  const selected = ma === undefined ? data?.ma_avatar : ma
  const mood = camXuc || data?.cam_xuc?.ma || 'binh_thuong'
  const ten = data?.cam_xuc?.ten || 'Avatar'
  const anh = data?.anh_avatar
  return <span className={`avatar-animation ${className}`} title={ten}>
    {!selected ? (anh && anh !== anhLoi ? <img src={anh} alt="Avatar" onError={() => setAnhLoi(anh)} /> : <span role="img" aria-label="Avatar mặc định">💜</span>) : <svg className={`bieu-cam ${mood}`} viewBox="0 0 100 100" role="img" aria-label={`Avatar ${ten}`}>
      <g className="nhan-vat">
        {selected === 'tho' ? <><ellipse cx="33" cy="25" rx="11" ry="24" fill={mau[selected]} /><ellipse cx="67" cy="25" rx="11" ry="24" fill={mau[selected]} /></> : selected === 'gau' ? <><circle cx="23" cy="24" r="16" fill={mau[selected]} /><circle cx="77" cy="24" r="16" fill={mau[selected]} /></> : <path d="M15 45 L13 9 L42 30 M58 30 L87 9 L85 45" fill={mau[selected] || mau.meo} />}
        <ellipse cx="50" cy="56" rx="39" ry="34" fill={mau[selected] || mau.meo} />
        <ellipse cx="50" cy="66" rx="26" ry="20" fill="#fff" opacity=".55" />
        <g stroke="#54394b" strokeWidth="3.5" fill="none" strokeLinecap="round">
          {mood === 'vui' ? <path d="M28 51 Q34 42 40 51 M60 51 Q66 42 72 51" /> : mood === 'met_moi' ? <path d="M28 52 H40 M60 52 H72" /> : <><path d="M34 48 V53 M66 48 V53" />{mood === 'tuc_gian' && <path d="M27 39 L41 44 M59 44 L73 39" />}{mood === 'lo_lang' && <path d="M27 41 L40 37 M60 37 L73 41" />}</>}
          <path d={['buon', 'tuc_gian', 'lo_lang'].includes(mood) ? 'M40 72 Q50 61 60 72' : mood === 'met_moi' ? 'M46 68 Q50 77 54 68 Z' : 'M40 65 Q50 77 60 65'} />
        </g>
        {mood === 'buon' && <path className="giot-le" d="M32 56 Q22 72 32 73 Q42 72 32 56" fill="#75bde8" />}
        {mood === 'met_moi' && <text x="72" y="30" fill="#76528c" fontSize="18">z</text>}
      </g>
    </svg>}
    {hienTen && data?.cam_xuc && <small className="ten-cam-xuc">{ten}</small>}
  </span>
}
