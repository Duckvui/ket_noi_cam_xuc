import PetChung from '../components/PetChung'
import ChinhSuaCamXuc from '../components/ChinhSuaCamXuc'
import AvatarAnimation from '../components/AvatarAnimation'
import { useEffect, useState } from 'react'
import { api } from '../api'
import BaiDang from '../components/wall/BaiDang'
import KhungDangBai from '../components/wall/KhungDangBai'
import ThanhBenPhai from '../components/wall/ThanhBenPhai'
import TinNoiBat from '../components/wall/TinNoiBat'
import ThanhBenTrai from '../components/wall/ThanhBenTrai'
import TrangCaNhan from './TrangCaNhan'
import ChatBox from '../components/wall/ChatBox'

const formatTime = (value) => new Date(value).toLocaleString('vi-VN')

function WallPage({ user, onLogout }) {
  const [posts, setPosts] = useState([])
  const [draft, setDraft] = useState('')
  const [photo, setPhoto] = useState(null)
  const [mood, setMood] = useState('')
  const [moods, setMoods] = useState([])
  const [visibility, setVisibility] = useState('Cong_Khai')
  const [publishing, setPublishing] = useState(false)
  const [postError, setPostError] = useState('')
  const [feedError, setFeedError] = useState('')
  const [loadingPosts, setLoadingPosts] = useState(true)
  const [liked, setLiked] = useState([])
  const [friendCount, setFriendCount] = useState(0)
  const [friendSearch, setFriendSearch] = useState('')
  const [submittedSearch, setSubmittedSearch] = useState('')
  const [profilePage, setProfilePage] = useState(null)
  const [chatOpen, setChatOpen] = useState(false)
  const [activeConversationId, setActiveConversationId] = useState(null)

  async function loadPosts() {
    setLoadingPosts(true); setFeedError('')
    try {
      const response = await api('/bai-viets', { headers: { Accept: 'application/json' } })
      if (!response.ok) throw new Error('Không tải được bài viết. Vui lòng thử lại.')
      const body = await response.json()
      setPosts((body.data || []).map((post) => ({ ...post, time: formatTime(post.published_at) })))
    } catch (error) { setFeedError(error.message) } finally { setLoadingPosts(false) }
  }

  useEffect(() => {
    Promise.resolve().then(loadPosts)
    api('/cam-xucs', { headers: { Accept: 'application/json' } }).then((response) => response.ok ? response.json() : null).then((body) => body?.data && setMoods(body.data)).catch(() => {})
  }, [])
  useEffect(() => () => { if (photo?.preview) URL.revokeObjectURL(photo.preview) }, [photo])

  function changePhoto(file) {
    if (photo?.preview) URL.revokeObjectURL(photo.preview)
    setPostError('')
    if (!file) return setPhoto(null)
    const supported = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
    if (!supported.includes(file.type) || file.size > 10 * 1024 * 1024) { setPhoto(null); return setPostError('Chỉ hỗ trợ ảnh JPG, PNG, WebP hoặc GIF, tối đa 10 MB.') }
    setPhoto({ file, preview: URL.createObjectURL(file) })
  }

  async function publish() {
    if (publishing || (!draft.trim() && !photo)) return
    setPublishing(true); setPostError('')
    const data = new FormData()
    data.append('NoiDung', draft.trim()); data.append('CheDoHienThi', visibility)
    if (mood) data.append('idCamXuc', mood)
    if (photo) data.append('photo', photo.file)
    try {
      const response = await api('/bai-viets', { method: 'POST', headers: { Accept: 'application/json' }, body: data })
      const body = await response.json().catch(() => ({}))
      if (!response.ok) throw new Error(Object.values(body.errors || {}).flat()[0] || body.message || 'Đăng bài thất bại.')
      setPosts((current) => [{ ...body.data, time: 'Vừa xong' }, ...current])
      setDraft(''); changePhoto(null); setMood(''); setVisibility('Cong_Khai')
    } catch (error) { setPostError(error.message) } finally { setPublishing(false) }
  }

  function toggleLike(id) { setLiked((current) => current.includes(id) ? current.filter((postId) => postId !== id) : [...current, id]) }
  async function startChat(recipientId) {
    const response = await api('/cuoc-tro-chuyens', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ recipient_id: recipientId }) })
    if (response.ok) {
      const body = await response.json()
      setActiveConversationId(body.data.id)
      setChatOpen(true)
    }
  }

  function openStoryConversation(result) {
    setActiveConversationId(result.idCuocTroChuyen)
    setChatOpen(true)
  }

  return <div className="wall"><header className="topbar"><a className="brand" href="#home"><span>✦</span> Cảm Xúc</a><form className="search" onSubmit={(event) => { event.preventDefault(); setSubmittedSearch(friendSearch.trim()) }}>⌕ <input value={friendSearch} onChange={(event) => setFriendSearch(event.target.value)} placeholder="Tìm tên hoặc nick bạn bè..." /><button type="submit" aria-label="Tìm">⌕</button></form><nav><button className="nav-active">⌂</button><button>♧</button><button onClick={() => setChatOpen(true)}>✉</button></nav><AvatarAnimation idTaiKhoan={user.id} /></header><main className="wall-layout"><ThanhBenTrai user={user} onLogout={onLogout} friendCount={friendCount} />{profilePage ? <TrangCaNhan profile={profilePage} onBack={() => setProfilePage(null)} onStartChat={startChat} /> : <section className="feed"><section className="page-heading"><p>TRANG TƯỜNG</p><h1>Hôm nay bạn cảm thấy thế nào?</h1></section><ChinhSuaCamXuc user={user} /><PetChung user={user} /><TinNoiBat user={user} onStoryReply={openStoryConversation} /><KhungDangBai user={user} draft={draft} setDraft={setDraft} photo={photo} onPhotoChange={changePhoto} mood={mood} moods={moods} setMood={setMood} visibility={visibility} setVisibility={setVisibility} onPublish={publish} saving={publishing} error={postError} />{loadingPosts && <p className="feed-message" role="status">Đang tải bài viết...</p>}{feedError && <p className="feed-message error" role="alert">{feedError} <button onClick={loadPosts}>Thử lại</button></p>}{!loadingPosts && !feedError && posts.length === 0 && <p className="feed-message">Chưa có bài viết nào. Hãy là người đầu tiên chia sẻ cảm xúc của bạn.</p>}{posts.map((post) => <BaiDang key={post.id} post={post} isLiked={liked.includes(post.id)} onToggleLike={() => toggleLike(post.id)} />)}</section>}<ThanhBenPhai onFriendCountChange={setFriendCount} queryFromHeader={submittedSearch} onOpenProfile={setProfilePage} /></main><ChatBox open={chatOpen} onClose={() => setChatOpen(false)} user={user} activeConversationId={activeConversationId} /></div>
}

export default WallPage
