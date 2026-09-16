import { useCallback, useEffect, useRef, useState } from 'react'
import { echo } from '../../realtime'
import { mediaUrl, requestData } from '../../services/nguoiDungService'
import UserAvatar from '../UserAvatar'

function ChatThread({ active, user, person, onSent }) {
  const [messages, setMessages] = useState([])
  const [text, setText] = useState('')
  const [photo, setPhoto] = useState(null)
  const [sending, setSending] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [olderBusy, setOlderBusy] = useState(false)
  const [hasMore, setHasMore] = useState(false)
  const [large, setLarge] = useState(null)
  const fileInput = useRef(null)
  const dialog = useRef(null)
  const initialized = useRef(false)
  const bottom = useRef(null)
  const lastMessageId = messages.at(-1)?.id
  const merge = useCallback(items => setMessages(current => [...new Map([...current, ...items].map(item => [item.id, item])).values()].sort((a, b) => a.id - b.id)), [])
  useEffect(() => () => { if (photo) URL.revokeObjectURL(photo.preview) }, [photo])
  useEffect(() => { bottom.current?.scrollIntoView({ block: 'nearest' }) }, [lastMessageId])
  useEffect(() => { if (large) dialog.current?.showModal(); else dialog.current?.close() }, [large])
  useEffect(() => {
    let cancelled = false
    let pending = false
    const controller = new AbortController()
    const load = async () => {
      if (pending || document.hidden) return
      pending = true
      try {
        const body = await requestData(`/cuoc-tro-chuyens/${active}/tin-nhans`, { signal: controller.signal })
        if (!cancelled) {
          merge(body.data)
          if (!initialized.current) { setHasMore(body.has_more); initialized.current = true }
          setLoading(false)
        }
      } catch (failure) { if (!cancelled) { setError(failure.message); setLoading(false) } }
      finally { pending = false }
    }
    void load()
    const timer = setInterval(load, 3000)
    const channel = echo?.private(`chat-user.${user.id}`)
    const changed = event => { if (event.conversation_id === active) void load() }
    channel?.listen('.tin-nhan.da-gui', changed)
    document.addEventListener('visibilitychange', load)
    return () => { cancelled = true; controller.abort(); clearInterval(timer); channel?.stopListening('.tin-nhan.da-gui', changed); document.removeEventListener('visibilitychange', load) }
  }, [active, user.id, merge])
  async function older() {
    if (olderBusy || !messages.length) return
    setOlderBusy(true)
    try { const body = await requestData(`/cuoc-tro-chuyens/${active}/tin-nhans?before=${messages[0].id}`); merge(body.data); setHasMore(body.has_more) }
    catch (failure) { setError(failure.message) }
    finally { setOlderBusy(false) }
  }
  function pick(event) {
    const file = event.target.files?.[0]
    event.target.value = ''
    setError(''); setPhoto(null)
    if (!file) return
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) return setError('Chọn ảnh JPG, PNG hoặc WebP tối đa 5 MB.')
    setPhoto({ file, preview: URL.createObjectURL(file) })
  }
  async function send(event) {
    event.preventDefault()
    if (sending || (!text.trim() && !photo)) return
    setSending(true); setError('')
    const data = new FormData()
    if (text.trim()) data.append('content', text.trim())
    if (photo) data.append('image', photo.file)
    try {
      const body = await requestData(`/cuoc-tro-chuyens/${active}/tin-nhans`, { method: 'POST', body: data })
      merge([body.data]); setText(''); setPhoto(null); onSent()
      window.dispatchEvent(new Event('pet-hoat-dong'))
    } catch (failure) { setError(failure.message) }
    finally { setSending(false) }
  }
  return <section className="chat-thread">
    <a className="chat-person" href={person?.id ? `#/profile/${person.id}` : '#home'}><UserAvatar user={person} size={32} clickable={false} /><h3>{person?.name || 'Cuộc trò chuyện'}</h3></a>
    <div className="chat-messages">
      {hasMore && <button disabled={olderBusy} onClick={older}>{olderBusy ? 'Đang tải...' : 'Tin nhắn trước đó'}</button>}
      {loading && <p role="status">Đang tải tin nhắn...</p>}{!loading && !messages.length && <p>Hãy gửi lời chào đầu tiên.</p>}
      {messages.map(message => <div className={`chat-bubble ${message.sender_id === user.id ? 'mine' : ''}`} key={message.id}>
        {message.image_url && <button type="button" onClick={() => setLarge(mediaUrl(message.image_url))} aria-label="Xem ảnh lớn"><img src={mediaUrl(message.image_url)} alt="Ảnh tin nhắn" /></button>}
        {message.content && <p>{message.content}</p>}<small>{new Date(message.sent_at).toLocaleString('vi-VN')}</small>
      </div>)}<div ref={bottom} />
    </div>
    {error && <p className="chat-error" role="alert">{error}</p>}
    {photo && <div className="chat-preview"><img src={photo.preview} alt="Ảnh sắp gửi" /><button disabled={sending} onClick={() => setPhoto(null)}>Hủy ảnh</button></div>}
    <form onSubmit={send}>
      <input ref={fileInput} type="file" hidden accept="image/jpeg,image/png,image/webp" onChange={pick} />
      <button type="button" disabled={sending} onClick={() => fileInput.current?.click()} aria-label="Chọn ảnh">📷</button>
      <input disabled={sending} maxLength={2000} value={text} onChange={event => setText(event.target.value)} placeholder="Nhập tin nhắn..." aria-label="Nội dung tin nhắn" />
      <button disabled={sending || (!text.trim() && !photo)}>{sending ? 'Đang gửi...' : 'Gửi'}</button>
    </form>
    <dialog className="chat-image-dialog" ref={dialog} onCancel={() => setLarge(null)} onClose={() => setLarge(null)}><button onClick={() => setLarge(null)}>Đóng</button>{large && <img src={large} alt="Ảnh tin nhắn phóng lớn" />}</dialog>
  </section>
}

export default function ChatBox({ open, onClose, user, activeConversationId }) {
  const [conversations, setConversations] = useState([])
  const [active, setActive] = useState(activeConversationId || null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const load = useCallback(async () => {
    try { const body = await requestData('/cuoc-tro-chuyens'); setConversations(body.data); setActive(current => current || body.data[0]?.id || null); setError('') }
    catch (failure) { setError(failure.message) }
    finally { setLoading(false) }
  }, [])
  useEffect(() => { if (activeConversationId) void Promise.resolve().then(() => setActive(activeConversationId)) }, [activeConversationId])
  useEffect(() => {
    if (!open) return undefined
    void Promise.resolve().then(() => { setLoading(true); return load() })
    const timer = setInterval(load, 5000)
    const channel = echo?.private(`chat-user.${user.id}`)
    channel?.listen('.tin-nhan.da-gui', load)
    return () => { clearInterval(timer); channel?.stopListening('.tin-nhan.da-gui', load) }
  }, [open, user.id, load])
  if (!open) return null
  const person = conversations.find(item => item.id === active)?.person
  return <aside className="chat-box"><header><strong>✉ Tin nhắn</strong><button onClick={onClose} aria-label="Đóng chat">✕</button></header>{error && <p role="alert">{error}</p>}<div className="chat-layout"><nav>{loading && <p>Đang tải...</p>}{!loading && !conversations.length && <p>Chưa có cuộc trò chuyện.</p>}{conversations.map(item => <button className={active === item.id ? 'active' : ''} onClick={() => { setActive(item.id); window.location.hash = `/messages/${item.id}` }} key={item.id}><UserAvatar user={item.person} size={30} clickable={false} /><b>{item.person.name}</b><small>{item.last_message || 'Bắt đầu trò chuyện'}</small></button>)}</nav>{active ? <ChatThread key={active} active={active} user={user} person={person} onSent={load} /> : <p>Chọn một người để bắt đầu chat.</p>}</div></aside>
}
