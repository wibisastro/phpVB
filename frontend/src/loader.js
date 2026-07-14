import { defineAsyncComponent } from 'vue'
import * as Vue from 'vue'
import { loadModule } from 'vue3-sfc-loader'
import eventBus from './eventBus.js'

// Jalur app phpVB (#6118 3b): file apps/<app>/vue/*.vue tetap di-load runtime
// tanpa build — penerus httpVueLoader adalah vue3-sfc-loader (author sama).
// Konvensi auto-scan PHP (document.php::component()) tidak berubah.

const styleCache = new Set()

// Compiler template Vue 3 men-transform asset URL relatif (mis.
// <img src="../../images/working.gif">) menjadi import — httpVueLoader lama
// tidak (compile in-browser Vue 2 membiarkan src apa adanya). Import asset
// dikembalikan sebagai URL string, jangan di-fetch/compile sebagai SFC.
const ASSET_RE = /\.(gif|png|jpe?g|svg|webp|ico|bmp|avif)(\?.*)?$/i

// Fallback path per-portal — perilaku yang sama dengan override
// httpVueLoader.httpRequest lama di cubeLayout (block customVueLoader):
// coba <app-halaman-sekarang>/vue/<file> dulu, baru URL asli.
function candidateUrls(url) {
  const file = url.split('/').pop()
  const appId = window.location.pathname.split('/')[1]
  const candidates = []
  if (appId && file) {
    const originUrl = `/${appId}/vue/${file}`
    if (originUrl !== url) candidates.push(originUrl)
  }
  candidates.push(url)
  return candidates
}

function looksLikeSfc(text) {
  const t = String(text || '').trim()
  return t.startsWith('<template') || t.startsWith('<script') || t.startsWith('<style')
}

// Kompat file .vue era httpVueLoader: `module.exports = {...}` → ESM.
// Berlaku juga untuk file .vue portal lama yang tidak ada di repo.
function toEsm(text) {
  return text.replace(/(^|\n)(\s*)module\.exports\s*=/, '$1$2export default')
}

async function fetchSfc(url) {
  let lastErr
  for (const candidate of candidateUrls(url)) {
    try {
      const res = await fetch(candidate, { headers: { 'X-Requested-With': 'fetch' } })
      if (!res.ok) { lastErr = new Error(`HTTP ${res.status} ${candidate}`); continue }
      const text = await res.text()
      if (!looksLikeSfc(text)) { lastErr = new Error(`Bukan SFC: ${candidate}`); continue }
      return toEsm(text)
    } catch (e) {
      lastErr = e
    }
  }
  throw lastErr || new Error(`Gagal memuat komponen: ${url}`)
}

export const sfcOptions = {
  moduleCache: { vue: Vue },
  async getFile(url) {
    if (ASSET_RE.test(url)) return { getContentData: () => '', type: '.asset' }
    const code = await fetchSfc(url)
    return { getContentData: () => code, type: '.vue' }
  },
  addStyle(textContent) {
    if (styleCache.has(textContent)) return
    styleCache.add(textContent)
    const style = document.createElement('style')
    style.textContent = textContent
    document.head.appendChild(style)
  },
  async handleModule(type, getContentData, path) {
    if (type === '.asset') return { default: String(path) }
    return undefined
  },
  log(type, ...args) {
    if (type === 'error') console.error('[phpvb sfc-loader]', ...args)
  },
}

// Pengganti drop-in httpVueLoader(url) — dipakai template layout & file .vue
// lama. Lazy (async component): file baru di-fetch saat komponen dirender,
// sehingga registrasi komponen yang filenya tidak ada tetap aman (paritas
// perilaku httpVueLoader).
export function loadVue(url) {
  return defineAsyncComponent({
    loader: () => loadModule(url, sfcOptions),
    onError(error, retry, fail) {
      console.error('[phpvb loadVue]', url, error)
      eventBus.$emit('openNotif', {
        class: 'is-warning',
        notification: `Komponen gagal dimuat: ${url.split('/').pop()}`,
      })
      fail()
    },
  })
}
