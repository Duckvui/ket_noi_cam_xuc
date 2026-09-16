import { useState } from 'react'

import { traLoiTin } from '../../services/tinService'

import './TraLoiTin.css'

export default function TraLoiTin({ story, onSent }) {
  const [message, setMessage] = useState('')
  const [sending, setSending] = useState(false)
  const [error, setError] = useState('')

  async function handleSubmit(event) {
    event.preventDefault()

    const content = message.trim()

    if (!content || sending) return

    setSending(true)
    setError('')

    try {
      const result = await traLoiTin(story.idTin, content)
      setMessage('')
      onSent?.(result)
    } catch (error) {
      setError(error.message)
    } finally {
      setSending(false)
    }
  }

  return (
    <form
      className="story-message"
      onSubmit={handleSubmit}
    >
      <div className="story-message-box">
        <input
          type="text"
          value={message}
          onChange={event =>
            setMessage(event.target.value)
          }
          placeholder="Gửi tin nhắn..."
          disabled={sending}
        />

        {message.trim() && (
          <button
            type="submit"
            disabled={sending}
          >
            {sending ? '...' : '➤'}
          </button>
        )}
      </div>

      {error && (
        <small className="story-message-error">
          {error}
        </small>
      )}
    </form>
  )
}
