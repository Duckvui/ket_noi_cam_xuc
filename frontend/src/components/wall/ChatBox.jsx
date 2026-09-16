import { useEffect, useState } from 'react'
import { api } from '../../api'

export default function ChatBox({ open, onClose, user, activeConversationId }) {
  const [conversations, setConversations] = useState([]); const [active, setActive] = useState(null); const [messages, setMessages] = useState([]); const [text, setText] = useState('')
  useEffect(() => { if (open) api('/cuoc-tro-chuyens', { headers: { Accept: 'application/json' } }).then(r => r.ok ? r.json() : null).then(body => { setConversations(body?.data || []); setActive(activeConversationId || body?.data?.[0]?.id || null) }) }, [open, activeConversationId])
  useEffect(() => { if (!open || !active) return undefined; const load = () => api(`/cuoc-tro-chuyens/${active}/tin-nhans`, { headers: { Accept: 'application/json' } }).then(r => r.ok ? r.json() : null).then(body => body?.data && setMessages(body.data)); load(); const timer = setInterval(load, 3000); return () => clearInterval(timer) }, [open, active])
  async function send(event) { event.preventDefault(); if (!text.trim() || !active) return; const r = await api(`/cuoc-tro-chuyens/${active}/tin-nhans`, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ content: text.trim() }) }); const body = await r.json().catch(() => ({})); if (r.ok) { setMessages(current => [...current, body.data]); setText('') } }
  if (!open) return null
  const person = conversations.find(item => item.id === active)?.person
  return <aside className="chat-box"><header><strong>✉ Tin nhắn</strong><button onClick={onClose}>✕</button></header><div className="chat-layout"><nav>{conversations.map(item => <button className={active === item.id ? 'active' : ''} onClick={() => setActive(item.id)} key={item.id}><b>{item.person.name}</b><small>{item.last_message || 'Bắt đầu trò chuyện'}</small></button>)}</nav><section className="chat-thread"><h3>{person?.name || 'Chưa có cuộc trò chuyện'}</h3><div className="chat-messages">{messages.map(message => <p className={message.sender_id === user.id ? 'mine' : ''} key={message.id}>{message.content}</p>)}</div>{active && <form onSubmit={send}><input value={text} onChange={e => setText(e.target.value)} placeholder="Nhập tin nhắn..." /><button>Gửi</button></form>}</section></div></aside>
}
