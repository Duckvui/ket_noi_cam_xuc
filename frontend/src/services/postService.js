import { api } from '../api'

async function request(url, options = {}) {
  const response = await api(url, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...options.headers,
    },
  })

  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new Error(body.message || 'Có lỗi xảy ra.')
  }

  return body.data
}

export function reactToPost(postId, type) {
  return request(`/bai-viets/${postId}/cam-xuc`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ type }),
  })
}

export function getPostComments(postId) {
  return request(`/bai-viets/${postId}/binh-luans`)
}

export function createPostComment(
  postId,
  content,
  parentId = null,
) {
  return request(`/bai-viets/${postId}/binh-luans`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      content,
      parent_id: parentId,
    }),
  })
}

export function updatePost(postId, content) {
  return request(`/bai-viets/${postId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      content,
    }),
  })
}

export function deletePost(postId) {
  return request(`/bai-viets/${postId}`, {
    method: 'DELETE',
  })
}