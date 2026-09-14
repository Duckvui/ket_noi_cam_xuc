import { useEffect, useState } from 'react'
import { api } from '../../api'
import { echo } from '../../realtime'

const reactions = [['Thich', '👍'], ['Yeu_Thich', '💗'], ['Haha', '😄'], ['Buon', '😢'], ['Tuc_Gian', '😠']]

function PostCard({ post }) {
  const [reaction, setReaction] = useState(null)
  const [likes, setLikes] = useState(post.likes)
  const [comments, setComments] = useState([])
  const [commentCount, setCommentCount] = useState(post.comments)
  const [showComments, setShowComments] = useState(false)
  const [text, setText] = useState('')
  const [replyTo, setReplyTo] = useState(null)
  const [commentError, setCommentError] = useState('')

  function addComment(comment) {
    setComments((current) => current.some((item) => item.id === comment.id) ? current : [...current, comment])
  }

  useEffect(() => {
    if (!echo) return undefined
    const channel = echo.channel(`post.${post.id}`)
    channel.listen('.comment.created', ({ comment }) => addComment(comment))
    return () => echo.leave(`post.${post.id}`)
  }, [post.id])
  async function react(type) { const response = await api(`/bai-viets/${post.id}/cam-xuc`, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ type }) }); if (response.ok) { const body = await response.json(); setLikes(body.data.likes); setReaction(body.data.reaction) } }
  async function toggleComments() { if (!showComments) { const response = await api(`/bai-viets/${post.id}/binh-luans`, { headers: { Accept: 'application/json' } }); if (response.ok) { const data = (await response.json()).data; setComments(data); setCommentCount(data.length) } } setShowComments(!showComments) }
  async function comment(event) { event.preventDefault(); if (!text.trim()) return; setCommentError(''); const response = await api(`/bai-viets/${post.id}/binh-luans`, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ content: text, parent_id: replyTo?.id || null }) }); const body = await response.json().catch(() => ({})); if (!response.ok) return setCommentError(body.message || 'Không thể gửi bình luận.'); addComment(body.data); setText(''); setReplyTo(null) }
  const roots = comments.filter((item) => !item.parent_id)
  const repliesFor = (id) => comments.filter((item) => item.parent_id === id)
  const renderComment = (item, isReply = false) => <div className={isReply ? 'comment reply' : 'comment'} key={item.id}><p><strong>{item.name}</strong> {item.content}</p><button type="button" className="reply-button" onClick={() => { setReplyTo(item); setText('') }}>Trả lời</button>{repliesFor(item.id).map((reply) => renderComment(reply, true))}</div>
  return <article className="post"><header><div className="avatar">{post.avatar || '💜'}</div><div><strong>{post.name}</strong><p>{post.time} {post.mood && <>· <em>{post.mood_icon} đang {post.mood.toLowerCase()}</em></>}</p></div><button className="more">•••</button></header>{post.content && <p className="post-content">{post.content}</p>}{post.image_url ? <img className="post-photo" src={post.image_url} alt="Ảnh trong bài viết" /> : post.image && <div className="post-image" style={{ background: post.color }}><span>{post.image}</span><p>những điều nhỏ bé đáng yêu</p></div>}<div className="post-stats"><span>{reaction ? reactions.find(([type]) => type === reaction)?.[1] : '💗'} {likes}</span><span>{Math.max(commentCount, comments.length)} bình luận</span></div><div className="reaction-bar">{reactions.map(([type, icon]) => <button key={type} onClick={() => react(type)}>{icon}</button>)}</div><div className="post-actions"><button className={reaction ? 'is-liked' : ''} onClick={() => react('Thich')}>♡ {reaction ? 'Đã bày tỏ' : 'Thích'}</button><button onClick={toggleComments}>◌ Bình luận</button><button>↗ Chia sẻ</button></div>{showComments && <div className="comments">{roots.map((item) => renderComment(item))}{replyTo && <p className="replying-to">Đang trả lời <strong>{replyTo.name}</strong> <button type="button" onClick={() => setReplyTo(null)}>Hủy</button></p>}{commentError && <p className="comment-error">{commentError}</p>}<form onSubmit={comment}><input value={text} onChange={(event) => setText(event.target.value)} placeholder={replyTo ? `Trả lời ${replyTo.name}...` : 'Viết bình luận...'} /><button>Gửi</button></form></div>}</article>
}
export default PostCard
