import { useCallback, useEffect, useRef, useState } from 'react'
import { layTrangThai } from '../services/DichVuCamXuc'
import { echo } from '../realtime'

import { NguCanhCamXuc } from './NguCanhCamXucContext'

export function NhaCungCapCamXuc({ children }) {
  const [duLieu, setDuLieu] = useState({})
  const [loi, setLoi] = useState('')
  const ids = useRef(new Map())
  const version = useRef(0)
  const mounted = useRef(true)
  const loading = useRef(false)
  const capNhat = useCallback((data) => {
    version.current += 1
    setDuLieu((old) => ({ ...old, [data.idTaiKhoan]: data }))
  }, [])
  const taiLai = useCallback(async () => {
    if (loading.current || document.hidden) return
    const active = [...ids.current.keys()]
    if (!active.length) return
    loading.current = true
    const current = version.current
    try {
      const result = []
      for (let i = 0; i < active.length; i += 100) result.push(...await layTrangThai(active.slice(i, i + 100)))
      if (mounted.current && current === version.current) {
        setDuLieu((old) => ({ ...old, ...Object.fromEntries(result.map((item) => [item.idTaiKhoan, item])) }))
        setLoi('')
      }
    } catch (error) { if (mounted.current) setLoi(error.message) }
    finally { loading.current = false }
  }, [])
  const dangKy = useCallback((id) => {
    if (!id) return () => {}
    ids.current.set(id, (ids.current.get(id) || 0) + 1)
    if (ids.current.get(id) === 1 && echo) echo.private(`cam-xuc.${id}`).listen('.cam-xuc.thay-doi', taiLai)
    const timer = setTimeout(taiLai, 50)
    return () => {
      clearTimeout(timer)
      const count = (ids.current.get(id) || 1) - 1
      if (count) ids.current.set(id, count)
      else { ids.current.delete(id); echo?.leave(`cam-xuc.${id}`) }
    }
  }, [taiLai])
  useEffect(() => {
    mounted.current = true
    const timer = setInterval(taiLai, 15000)
    document.addEventListener('visibilitychange', taiLai)
    return () => { mounted.current = false; clearInterval(timer); document.removeEventListener('visibilitychange', taiLai) }
  }, [taiLai])
  return <NguCanhCamXuc.Provider value={{ duLieu, loi, dangKy, capNhat, taiLai }}>{children}</NguCanhCamXuc.Provider>
}
