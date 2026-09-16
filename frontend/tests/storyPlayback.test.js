import test from 'node:test'
import assert from 'node:assert/strict'
import { adjacentStory, groupStories, STORY_DURATION } from '../src/services/storyPlayback.js'

const now = Date.parse('2026-09-16T12:00:00Z')
const story = (id, user, age, expires = now + 60000) => ({
  idTin: id, idTaiKhoan: user, ten_hien_thi: 'Same name',
  ThoiGianDang: new Date(now - age).toISOString(), ThoiGianHetHan: new Date(expires).toISOString(),
})

test('groups by account, puts self first, orders oldest first and excludes expired stories', () => {
  const groups = groupStories([story(4, 2, 100), story(2, 1, 200), story(3, 2, 300), story(1, 1, 400), story(5, 3, 500, now)], '1', now)
  assert.deepEqual(groups.map(group => [group.id, group.stories.map(item => item.idTin)]), [['1', [1, 2]], ['2', [3, 4]]])
  assert.equal(STORY_DURATION, 30000)
})

test('next and previous traverse own stories and account boundaries with finite endpoints', () => {
  const groups = groupStories([story(1, 1, 500), story(2, 1, 400), story(3, 2, 300), story(4, 2, 200), story(5, 3, 600)], 1, now)
  for (let id = 1; id < 5; id++) assert.equal(adjacentStory(groups, id, 1)?.idTin, id + 1)
  for (let id = 5; id > 1; id--) assert.equal(adjacentStory(groups, id, -1)?.idTin, id - 1)
  assert.equal(adjacentStory(groups, 1, -1), null)
  assert.equal(adjacentStory(groups, 5, 1), null)
  assert.equal(adjacentStory([], 1, 1), null)
})

test('ties use IDs and expired-only owners disappear', () => {
  const groups = groupStories([story(8, 3, 10), story(7, 3, 10), story(9, 1, 20, now - 1)], 1, now)
  assert.deepEqual(groups.map(group => group.stories.map(item => item.idTin)), [[7, 8]])
})
