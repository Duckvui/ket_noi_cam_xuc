import { useEffect, useState } from 'react'
import { dangTin } from '../../services/tinService'
import { storyBackgrounds } from '../../services/storyPlayback'

export default function TaoTin({ onPublished }) {
  const [kind, setKind] = useState(null)
  const [content, setContent] = useState('')
  const [file, setFile] = useState(null)
  const [preview, setPreview] = useState('')
  const [background, setBackground] = useState('purple')
  const [visibility, setVisibility] = useState('Cong_Khai')
  const [publishing, setPublishing] = useState(false)
  const [error, setError] = useState('')
  useEffect(() => {
    if (!file) return undefined
    const url = URL.createObjectURL(file)
    const timer = setTimeout(() => setPreview(url), 0)
    return () => { clearTimeout(timer); URL.revokeObjectURL(url) }
  }, [file])
  function chooseFile(event) {
    const next = event.target.files?.[0]
    setError(''); setFile(null); setPreview('')
    if (!next) return
    const image = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(next.type)
    const video = ['video/mp4', 'video/webm'].includes(next.type)
    if ((!image && !video) || next.size > (image ? 10 : 50) * 1024 * 1024) {
      setError('Chọn ảnh JPG, PNG, WebP, GIF tối đa 10 MB hoặc video MP4, WebM tối đa 50 MB.')
      event.target.value = ''; return
    }
    setFile(next)
  }
  async function publish(event) {
    event.preventDefault()
    if (publishing || (kind === 'text' ? !content.trim() : !file)) return
    setPublishing(true); setError('')
    try {
      const story = await dangTin({ loaiTin: kind === 'text' ? 'VAN_BAN' : file.type.startsWith('video/') ? 'VIDEO' : 'ANH', noiDung: content.trim(), media: kind === 'text' ? null : file, cheDoHienThi: visibility, mauNen: background })
      onPublished(story)
    } catch (failure) { setError(failure.message) }
    finally { setPublishing(false) }
  }
  if (!kind) return <main className="story-create-choices">
    <button className="story-choice story-choice-media" onClick={() => setKind('media')}><span>▧</span>Tạo tin có ảnh hoặc video</button>
    <button className="story-choice story-choice-text" onClick={() => setKind('text')}><span>Aa</span>Tạo tin dạng văn bản</button>
  </main>
  return <main className="story-compose"><form onSubmit={publish}>
    <fieldset disabled={publishing}>
      <button type="button" className="story-back" onClick={() => { setKind(null); setFile(null); setPreview(''); setError('') }}>← Chọn loại tin</button>
      <h2>{kind === 'text' ? 'Tạo tin dạng văn bản' : 'Tạo tin có ảnh hoặc video'}</h2>
      {kind === 'media' && <label>Ảnh hoặc video<input type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" required onChange={chooseFile} /><small>Ảnh tối đa 10 MB · Video tối đa 50 MB</small></label>}
      <label>{kind === 'text' ? 'Nội dung' : 'Chú thích'}<textarea maxLength={2000} rows={5} required={kind === 'text'} value={content} onChange={event => setContent(event.target.value)} placeholder="Bạn đang nghĩ gì?" /></label>
      {kind === 'text' && <div className="story-colors" aria-label="Màu nền">{Object.entries(storyBackgrounds).map(([key, value], index) => <button type="button" key={key} aria-label={`Nền ${['tím', 'xanh dương', 'hồng', 'xanh lá', 'tối'][index]}`} aria-pressed={background === key} style={{ background: value }} onClick={() => setBackground(key)} />)}</div>}
      <label>Ai có thể xem?<select value={visibility} onChange={event => setVisibility(event.target.value)}><option value="Cong_Khai">Công khai</option><option value="Ban_Be">Bạn bè</option><option value="Chi_Minh_Toi">Chỉ mình tôi</option></select></label>
      {error && <p className="story-error" role="alert">{error}</p>}
      <button className="story-submit" type="submit" disabled={publishing || (kind === 'text' ? !content.trim() : !file)}>{publishing ? 'Đang đăng...' : 'Đăng tin'}</button>
    </fieldset>
  </form><section className="story-preview" aria-label="Xem trước"><h3>Xem trước</h3><div className="story-preview-card" style={kind === 'text' ? { background: storyBackgrounds[background] } : undefined}>
    {kind === 'text' ? <p className="story-text-content">{content || 'Nhập nội dung tin của bạn'}</p> : preview ? <>{file.type.startsWith('video/') ? <video src={preview} controls /> : <img src={preview} alt="Xem trước tin" />}{content && <p className="story-caption">{content}</p>}</> : <p>Chọn ảnh hoặc video để xem trước</p>}
  </div></section></main>
}
