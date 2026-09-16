// Local UI fixture only. No requests or posts reach a real account.
import React from 'react'
import { createRoot } from 'react-dom/client'
import TinNoiBat from '../src/components/wall/TinNoiBat'
import { NguCanhCamXuc } from '../src/components/NguCanhCamXucContext'
import '../src/App.css'
import '../src/index.css'

const now = Date.now()
let stories = [
  [1, 1, 'Tài khoản thử', 'Tin của tôi 1', 'purple'],
  [2, 1, 'Tài khoản thử', 'Tin của tôi 2', 'blue'],
  [3, 2, 'Nguyễn Văn A', 'Tin của A 1', 'pink'],
  [4, 2, 'Nguyễn Văn A', 'Tin của A 2', 'green'],
  [5, 3, 'Trần Văn B', 'Tin của B 1', 'dark'],
].map(([idTin, idTaiKhoan, ten_hien_thi, NoiDung, MauNen]) => ({
  idTin, idTaiKhoan, ten_hien_thi, NoiDung, MauNen, LoaiTin: 'VAN_BAN',
  ThoiGianDang: new Date(now - (idTin === 5 ? 10 : 6 - idTin) * 60000).toISOString(), ThoiGianHetHan: new Date(now + 86400000).toISOString(),
  is_owner: idTaiKhoan === 1, views_count: 0, viewers: [],
}))
window.fetch = async (url, options = {}) => {
  const id = Number(String(url).split('/')[3])
  let data = stories
  if (String(url) === '/api/tins' && options.method === 'POST') {
    const form = options.body
    const media = form.get('media')
    data = { ...stories[0], idTin: stories.length + 1, LoaiTin: form.get('LoaiTin'), NoiDung: form.get('NoiDung'), MauNen: form.get('MauNen'), media_url: media ? URL.createObjectURL(media) : null, ThoiGianDang: new Date().toISOString() }
    stories = [...stories, data]
  } else if (id) data = stories.find(story => story.idTin === id)
  return new Response(JSON.stringify({ data }), { status: 200, headers: { 'Content-Type': 'application/json' } })
}
const duLieu = { 1: { ma_avatar: 'meo' }, 2: { ma_avatar: 'gau' }, 3: { ma_avatar: 'tho' } }
createRoot(document.getElementById('root')).render(<NguCanhCamXuc.Provider value={{ duLieu }}><div style={{ padding: 24 }}><p>Kiểm thử giao diện với dữ liệu giả lập · 2 tin của tôi → 2 tin của A → 1 tin của B</p><TinNoiBat user={{ id: 1, ten_hien_thi: 'Tài khoản thử' }} /></div></NguCanhCamXuc.Provider>)
