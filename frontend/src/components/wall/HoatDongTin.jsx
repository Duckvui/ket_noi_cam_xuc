import AvatarAnimation from '../AvatarAnimation'
import { useState } from 'react'

import './HoatDongTin.css'

export default function HoatDongTin({
  story,
}) {
  const [open, setOpen] = useState(false)

  if (!story?.is_owner) return null

  return (
    <>
      <button
        type="button"
        className="story-activity-button"
        onClick={() => setOpen(current => !current)}
      >
        ◉ {story.views_count || 0}

        {(story.reactions_count || 0) > 0 && (
          <span>
            👍 {story.reactions_count}
          </span>
        )}
      </button>

      {open && (
        <aside className="story-activity-panel">
          <div className="story-activity-header">
            <h3>Hoạt động của tin</h3>

            <button
              type="button"
              onClick={() => setOpen(false)}
            >
              ✕
            </button>
          </div>

          <div className="story-activity-summary">
            <div>
              <strong>{story.views_count || 0}</strong>
              <span>Lượt xem</span>
            </div>

            <div>
              <strong>
                {story.reactions_count || 0}
              </strong>
              <span>Cảm xúc</span>
            </div>
          </div>

          <div className="story-viewers">
            <h4>Người đã xem</h4>

            {story.viewers?.length > 0 ? (
              story.viewers.map(viewer => (
                <div
                  className="story-viewer-item"
                  key={viewer.id}
                >
                  <AvatarAnimation idTaiKhoan={viewer.id} />

                  <span>{viewer.name}</span>
                </div>
              ))
            ) : (
              <p>Chưa có người xem.</p>
            )}
          </div>
        </aside>
      )}
    </>
  )
}
