import AvatarAnimation from '../AvatarAnimation'
import { useEffect, useState } from 'react'
import { echo } from '../../realtime'

import {
  createPostComment,
  getPostComments,
  reactToPost,
  updatePost,
  deletePost,
} from '../../services/postService'

const reactions = [
  ['Thich', '👍'],
  ['Yeu_Thich', '💗'],
  ['Haha', '😄'],
  ['Buon', '😢'],
  ['Tuc_Gian', '😠'],
]

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
    setComments((current) => {
      const commentExists = current.some(
        (item) => item.id === comment.id,
      )

      return commentExists
        ? current
        : [...current, comment]
    })
  }

  useEffect(() => {
    if (!echo) return undefined

    const channelName = `post.${post.id}`
    const channel = echo.channel(channelName)

    channel.listen('.comment.created', ({ comment }) => {
      addComment(comment)
    })

    return () => {
      echo.leave(channelName)
    }
  }, [post.id])

  async function react(type) {
    try {
      const data = await reactToPost(post.id, type)

      setLikes(data.likes)
      setReaction(data.reaction)
    } catch (error) {
      console.error(error.message)
    }
  }

  async function toggleComments() {
    if (!showComments) {
      try {
        const data = await getPostComments(post.id)

        setComments(data)
        setCommentCount(data.length)
      } catch (error) {
        setCommentError(error.message)
        return
      }
    }

    setShowComments((current) => !current)
  }

  async function comment(event) {
    event.preventDefault()

    const content = text.trim()

    if (!content) return

    setCommentError('')

    try {
      const newComment = await createPostComment(
        post.id,
        content,
        replyTo?.id || null,
      )

      addComment(newComment)
      setText('')
      setReplyTo(null)
    } catch (error) {
      setCommentError(
        error.message || 'Không thể gửi bình luận.',
      )
    }
  }

  const rootComments = comments.filter(
    (item) => !item.parent_id,
  )

  function repliesFor(commentId) {
    return comments.filter(
      (item) => item.parent_id === commentId,
    )
  }

  function renderComment(item, isReply = false) {
    return (
      <div
        className={isReply ? 'comment reply' : 'comment'}
        key={item.id}
      >
        <p>
          <AvatarAnimation idTaiKhoan={item.idTaiKhoan} /> <strong>{item.name}</strong> {item.content}
        </p>

        <button
          type="button"
          className="reply-button"
          onClick={() => {
            setReplyTo(item)
            setText('')
          }}
        >
          Trả lời
        </button>

        {repliesFor(item.id).map((reply) =>
          renderComment(reply, true),
        )}
      </div>
    )
  }

  const reactionIcon = reaction
    ? reactions.find(([type]) => type === reaction)?.[1]
    : '💗'

  const displayedCommentCount = Math.max(
    commentCount,
    comments.length,
  )

  return (
    <article className="post">
      <header>
        <AvatarAnimation idTaiKhoan={post.idTaiKhoan} hienTen />

        <div>
          <strong>{post.name}</strong>

          <p>
            {post.time}

            {post.mood && (
              <>
                {' '}·{' '}
                <em>
                  {post.mood_icon} đang{' '}
                  {post.mood.toLowerCase()}
                </em>
              </>
            )}
          </p>
        </div>

        <button type="button" className="more">
          •••
        </button>
      </header>

      {post.content && (
        <p className="post-content">
          {post.content}
        </p>
      )}

      {post.image_url ? (
        <img
          className="post-photo"
          src={post.image_url}
          alt="Ảnh trong bài viết"
        />
      ) : (
        post.image && (
          <div
            className="post-image"
            style={{ background: post.color }}
          >
            <span>{post.image}</span>
            <p>những điều nhỏ bé đáng yêu</p>
          </div>
        )
      )}

      <div className="post-stats">
        <span>
          {reactionIcon} {likes}
        </span>

        <span>
          {displayedCommentCount} bình luận
        </span>
      </div>

      <div className="reaction-bar">
        {reactions.map(([type, icon]) => (
          <button
            type="button"
            key={type}
            onClick={() => react(type)}
          >
            {icon}
          </button>
        ))}
      </div>

      <div className="post-actions">
        <button
          type="button"
          className={reaction ? 'is-liked' : ''}
          onClick={() => react('Thich')}
        >
          ♡ {reaction ? 'Đã bày tỏ' : 'Thích'}
        </button>

        <button
          type="button"
          onClick={toggleComments}
        >
          ◌ Bình luận
        </button>

        <button type="button">
          ↗ Chia sẻ
        </button>
      </div>

      {showComments && (
        <div className="comments">
          {rootComments.map((item) =>
            renderComment(item),
          )}

          {replyTo && (
            <p className="replying-to">
              Đang trả lời{' '}
              <strong>{replyTo.name}</strong>

              <button
                type="button"
                onClick={() => setReplyTo(null)}
              >
                Hủy
              </button>
            </p>
          )}

          {commentError && (
            <p className="comment-error">
              {commentError}
            </p>
          )}

          <form onSubmit={comment}>
            <input
              value={text}
              onChange={(event) =>
                setText(event.target.value)
              }
              placeholder={
                replyTo
                  ? `Trả lời ${replyTo.name}...`
                  : 'Viết bình luận...'
              }
            />

            <button type="submit">
              Gửi
            </button>
          </form>
        </div>
      )}
    </article>
  )
}

export default PostCard