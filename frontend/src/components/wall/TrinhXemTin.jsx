import { useEffect, useRef, useState } from 'react'
import { STORY_DURATION, storyBackgrounds } from '../../services/storyPlayback'
import AvatarAnimation from '../AvatarAnimation'
import HoatDongTin from './HoatDongTin'
import TraLoiTin from './TraLoiTin'

const reactions = [['Thich', '👍'], ['Yeu_Thich', '❤️'], ['Haha', '😆'], ['Wow', '😮'], ['Buon', '😢'], ['Tuc_Gian', '😡']]
const mediaUrl = path => {
  if (!path) return ''
  if (/^(https?:|blob:)/.test(path)) return path
  const base = (import.meta.env.VITE_API_BASE_URL || '/api').replace(/\/$/, '')
  return `${base}${path.slice('/api'.length)}`
}

export default function TrinhXemTin({ story, group, previous, onPrevious, onNext, onReact, onStoryReply }) {
  const [progress, setProgress] = useState(0)
  const [paused, setPaused] = useState(false)
  const [focused, setFocused] = useState(false)
  const [hidden, setHidden] = useState(document.hidden)
  const [ready, setReady] = useState(story.LoaiTin === 'VAN_BAN')
  const [error, setError] = useState('')
  const [reacting, setReacting] = useState(false)
  const video = useRef(null)
  const elapsed = useRef(0)
  const advanced = useRef(false)
  const next = useRef(onNext)
  const back = useRef(onPrevious)
  useEffect(() => { next.current = onNext; back.current = onPrevious }, [onNext, onPrevious])
  const stopped = paused || focused || hidden
  const index = group.stories.findIndex(item => item.idTin === story.idTin)
  function finish() {
    if (advanced.current) return
    advanced.current = true
    next.current()
  }
  useEffect(() => {
    const visibility = () => setHidden(document.hidden)
    const keyboard = event => {
      if (event.target.closest('input,textarea,select,button')) return
      if (event.key === 'ArrowRight') { event.preventDefault(); next.current() }
      if (event.key === 'ArrowLeft') { event.preventDefault(); back.current() }
      if (event.code === 'Space') { event.preventDefault(); setPaused(value => !value) }
    }
    document.addEventListener('visibilitychange', visibility)
    document.addEventListener('keydown', keyboard)
    return () => { document.removeEventListener('visibilitychange', visibility); document.removeEventListener('keydown', keyboard) }
  }, [])
  useEffect(() => {
    if (story.LoaiTin === 'VIDEO' || !ready || stopped) return undefined
    let last = performance.now()
    const timer = setInterval(() => {
      const current = performance.now()
      elapsed.current += current - last
      last = current
      setProgress(Math.min(100, elapsed.current / STORY_DURATION * 100))
      if (elapsed.current >= STORY_DURATION && !advanced.current) { advanced.current = true; next.current() }
    }, 50)
    return () => clearInterval(timer)
  }, [story.LoaiTin, ready, stopped])
  useEffect(() => {
    if (!video.current) return
    if (stopped) video.current.pause()
    else video.current.play().catch(() => { setPaused(true) })
  }, [stopped])
  async function react(type) {
    if (reacting) return
    setReacting(true)
    try { await onReact(type); setError('') }
    catch (failure) { setError(failure.message) }
    finally { setReacting(false) }
  }
  return <div className="story-player">
    <button type="button" className="story-arrow story-previous" aria-label="Tin trước" disabled={!previous} onClick={onPrevious}>‹</button>
    <div className="story-player-center">
      <article className="story-canvas" style={story.LoaiTin === 'VAN_BAN' ? { background: storyBackgrounds[story.MauNen] || storyBackgrounds.purple } : undefined}>
        <div className="story-overlay"><div className="story-progress" aria-label={`Tin ${index + 1} trên ${group.stories.length}`}>
          {group.stories.map((item, position) => <span key={item.idTin}><i style={{ width: `${position < index ? 100 : position === index ? progress : 0}%` }} /></span>)}
        </div><header className="story-author"><AvatarAnimation idTaiKhoan={story.idTaiKhoan} /><div><strong>{story.ten_hien_thi}</strong><time dateTime={story.ThoiGianDang}>{new Date(story.ThoiGianDang).toLocaleString('vi-VN')}</time></div><button type="button" onClick={() => setPaused(value => !value)} aria-label={paused ? 'Tiếp tục tin' : 'Tạm dừng tin'}>{paused ? '▶' : 'Ⅱ'}</button></header></div>
        {story.LoaiTin === 'VAN_BAN' && <p className="story-text-content">{story.NoiDung}</p>}
        {story.LoaiTin === 'ANH' && <img className="story-media" src={mediaUrl(story.media_url)} alt={story.NoiDung || 'Ảnh trong tin'} onLoad={() => setReady(true)} onError={() => { setError('Không tải được ảnh. Bạn có thể chuyển sang tin tiếp theo.'); setReady(true) }} />}
        {story.LoaiTin === 'VIDEO' && <video ref={video} className="story-media" src={mediaUrl(story.media_url)} autoPlay muted playsInline controls onLoadedData={() => setReady(true)} onTimeUpdate={event => {
          const media = event.currentTarget
          if (Number.isFinite(media.duration) && media.duration > 0) setProgress(Math.min(100, media.currentTime / media.duration * 100))
        }} onEnded={finish} onError={() => setError('Không phát được video. Hãy chuyển sang tin tiếp theo.')} />}
        {story.LoaiTin !== 'VAN_BAN' && story.NoiDung && <p className="story-caption">{story.NoiDung}</p>}
        {!ready && !error && <p className="story-media-status" role="status">Đang tải nội dung...</p>}
      </article>
      {error && <p className="story-error" role="alert">{error}</p>}
      <footer className="story-player-footer" onFocusCapture={() => setFocused(true)} onBlurCapture={event => { if (!event.currentTarget.contains(event.relatedTarget)) setFocused(false) }}>
        {story.is_owner ? <HoatDongTin story={story} /> : <div className="story-actions"><TraLoiTin story={story} onSent={onStoryReply} /><div className="story-reactions">{reactions.map(([key, icon]) => <button type="button" disabled={reacting} className={story.my_reaction === key ? 'active' : ''} onClick={() => react(key)} key={key} aria-label={key}>{icon}</button>)}</div></div>}
      </footer>
    </div>
    <button type="button" className="story-arrow story-next" aria-label="Tin tiếp theo" onClick={onNext}>›</button>
  </div>
}
