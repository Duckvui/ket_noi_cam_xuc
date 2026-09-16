import { useEffect, useMemo, useRef, useState } from 'react'
import { layDanhSachTin, thaCamXucTin, xemTin } from '../../services/tinService'
import { adjacentStory, groupStories } from '../../services/storyPlayback'
import AvatarAnimation from '../AvatarAnimation'
import TaoTin from './TaoTin'
import TrinhXemTin from './TrinhXemTin'
import './TinNoiBat.css'

export default function TinNoiBat({ user, onStoryReply }) {
  const [stories, setStories] = useState([])
  const [selectedId, setSelectedId] = useState(null)
  const [mode, setMode] = useState(null)
  const [now, setNow] = useState(Date.now)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const dialog = useRef(null)
  const groups = useMemo(() => groupStories(stories, user.id, now), [stories, user.id, now])
  const group = groups.find(item => item.stories.some(story => story.idTin === selectedId))
  const selected = group?.stories.find(story => story.idTin === selectedId)
  async function load() {
    setLoading(true)
    try { setStories(await layDanhSachTin()); setNow(Date.now()); setError('') }
    catch (failure) { setError(failure.message) }
    finally { setLoading(false) }
  }
  useEffect(() => { void Promise.resolve().then(load) }, [])
  useEffect(() => {
    window.addEventListener('quan-he-thay-doi', load)
    return () => window.removeEventListener('quan-he-thay-doi', load)
  }, [])
  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 1000)
    return () => clearInterval(timer)
  }, [])
  useEffect(() => {
    if (!mode) { dialog.current?.close(); return undefined }
    if (!dialog.current?.open) dialog.current?.showModal()
    const previous = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => { document.body.style.overflow = previous }
  }, [mode])
  useEffect(() => {
    if (mode !== 'view' || selectedId === null) return undefined
    let cancelled = false
    xemTin(selectedId).then(updated => {
      if (!cancelled) setStories(current => current.map(item => item.idTin === updated.idTin ? updated : item))
    }).catch(failure => { if (!cancelled) setError(failure.message) })
    return () => { cancelled = true }
  }, [selectedId, mode])
  // Continue past a story that expires while the viewer is open.
  useEffect(() => {
    if (mode !== 'view' || selected || selectedId === null) return
    const all = groupStories(stories, user.id, 0).flatMap(item => item.stories)
    const index = all.findIndex(item => item.idTin === selectedId)
    const next = all.slice(index + 1).find(item => new Date(item.ThoiGianHetHan).getTime() > now)
    const timer = setTimeout(() => setSelectedId(next?.idTin ?? null), 0)
    return () => clearTimeout(timer)
  }, [mode, selected, selectedId, stories, user.id, now])
  function openStory(story) { setSelectedId(story.idTin); setMode('view'); setError('') }
  function close() { setMode(null); setSelectedId(null); setError('') }
  function move(direction) {
    const next = adjacentStory(groups, selectedId, direction)
    if (next) openStory(next)
    else if (direction > 0) setSelectedId(null)
  }
  async function react(type) {
    const id = selectedId
    const updated = await thaCamXucTin(id, type)
    setStories(current => current.map(item => item.idTin === id ? updated : item))
  }
  function create() { setSelectedId(null); setMode('create'); setError('') }
  const mine = groups.find(item => item.id === String(user.id))
  const userButton = item => <button type="button" className={`story-person ${group?.id === item.id ? 'active' : ''}`} key={item.id} onClick={() => openStory(item.stories[0])}>
    <AvatarAnimation idTaiKhoan={Number(item.id)} /><span><strong>{item.name}</strong><small>{item.stories.length} tin</small></span>
  </button>
  return <section aria-label="Tin trong 24 giờ">
    <div className="stories story-list">
      <button type="button" className="story story-button" onClick={create}><span className="story-ring">＋</span><small>Tạo tin</small></button>
      {groups.map(item => <button type="button" className="story story-button" key={item.id} onClick={() => openStory(item.stories[0])}><span className="story-ring"><AvatarAnimation idTaiKhoan={Number(item.id)} /></span><small>{item.id === String(user.id) ? 'Tin của bạn' : item.name}</small></button>)}
      {loading && <small role="status">Đang tải tin...</small>}
      {!loading && !groups.length && <small>Chưa có tin. Chia sẻ khoảnh khắc đầu tiên!</small>}
    </div>
    {error && !mode && <p role="alert" className="story-error">{error} <button onClick={load}>Thử lại</button></p>}
    <dialog ref={dialog} className={`story-dialog ${mode === 'create' ? 'story-creator' : 'story-fullscreen'}`} aria-label={mode === 'create' ? 'Tạo tin' : 'Xem tin'} onCancel={event => { event.preventDefault(); close() }} onClose={close}>
      {mode && <div className="story-layout">
        <aside className="story-sidebar">
          <button type="button" className="story-close" onClick={close} aria-label="Đóng tin">✕</button>
          <h2>Tin của bạn</h2>
          {mode === 'create' ? <div className="story-person"><AvatarAnimation idTaiKhoan={user.id} /><strong>{user.ten_hien_thi || user.tai_khoan}</strong></div> : <>
            <button type="button" className="story-person" onClick={create}><span className="story-add">＋</span><span><strong>Tạo tin</strong><small>Chia sẻ ảnh, video hoặc văn bản</small></span></button>
            {mine && userButton(mine)}
            <h3>Tất cả tin</h3>
            {groups.filter(item => item.id !== String(user.id)).map(userButton)}
          </>}
        </aside>
        {mode === 'create' ? <TaoTin onPublished={story => { setStories(current => [...current, story]); openStory(story) }} /> : <main className="story-stage">
          {error && <p role="alert" className="story-error">{error} <button onClick={load}>Tải lại</button></p>}
          {selected ? <TrinhXemTin key={selected.idTin} story={selected} group={group} previous={Boolean(adjacentStory(groups, selectedId, -1))} onPrevious={() => move(-1)} onNext={() => move(1)} onReact={react} onStoryReply={result => { close(); onStoryReply?.(result) }} /> : <div className="story-empty"><h2>{groups.length ? 'Bạn đã xem hết tin' : 'Chưa có tin đang hiển thị'}</h2><p>Chọn một người bên trái để xem lại hoặc tạo tin mới.</p><button className="story-submit" onClick={create}>Tạo tin</button></div>}
        </main>}
      </div>}
    </dialog>
  </section>
}
