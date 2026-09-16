export const STORY_DURATION = 30_000
export const storyBackgrounds = {
  purple: 'linear-gradient(135deg, #7138b5, #bc65da)',
  blue: 'linear-gradient(135deg, #0759a6, #36a6d8)',
  pink: 'linear-gradient(135deg, #ad245c, #ed799a)',
  green: 'linear-gradient(135deg, #15654e, #42ad8d)',
  dark: 'linear-gradient(135deg, #182333, #45556a)',
}

export function groupStories(stories, userId, now = Date.now()) {
  const groups = new Map()
  const active = stories.filter(story => new Date(story.ThoiGianHetHan).getTime() > now)
    .sort((a, b) => new Date(a.ThoiGianDang) - new Date(b.ThoiGianDang) || a.idTin - b.idTin)
  for (const story of active) {
    const key = String(story.idTaiKhoan)
    if (!groups.has(key)) groups.set(key, { id: key, name: story.ten_hien_thi, stories: [] })
    groups.get(key).stories.push(story)
  }
  return [...groups.values()].sort((a, b) => {
    if (a.id === String(userId)) return -1
    if (b.id === String(userId)) return 1
    return new Date(b.stories.at(-1).ThoiGianDang) - new Date(a.stories.at(-1).ThoiGianDang) || Number(a.id) - Number(b.id)
  })
}

export function adjacentStory(groups, id, direction) {
  const stories = groups.flatMap(group => group.stories)
  const index = stories.findIndex(story => story.idTin === id)
  return index < 0 ? null : stories[index + direction] || null
}
