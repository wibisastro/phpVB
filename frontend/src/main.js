// Entry bundle inti phpVB — Fase 3b #6118 (v3.0.1).
//
// Dua jalur frontend:
// 1. Jalur CORE (file ini): Vue 3 + mitt bridge + komponen inti + wrapper
//    b-* BS5 → build Vite → public/dist/main.js (DI-COMMIT, deploy tanpa Node).
// 2. Jalur APP: apps/<app>/vue/*.vue tetap runtime-load via vue3-sfc-loader
//    (penerus httpVueLoader) — lihat loader.js. Konvensi auto-scan PHP
//    (document.php::component()) tidak berubah.
import * as Vue from 'vue'
import { createApp } from 'vue'
import eventBus from './eventBus.js'
import { loadVue, sfcOptions } from './loader.js'
import bvComponents from './bv.js'

import Gov2Table from './components/Gov2Table.vue'
import Gov2Pagination from './components/Gov2Pagination.vue'
import Gov2Notification from './components/Gov2Notification.vue'
import Gov2Search from './components/Gov2Search.vue'
import Gov2FormField from './components/Gov2FormField.vue'
import Gov2Button from './components/Gov2Button.vue'
import Gov2Tagging from './components/Gov2Tagging.vue'
import Gov2Tagged from './components/Gov2Tagged.vue'
import Gov2Checkbox from './components/Gov2Checkbox.vue'
import Gov2Progress from './components/Gov2Progress.vue'
import Gov2Session from './components/Gov2Session.vue'

const coreComponents = {
  'gov2table': Gov2Table,
  'gov2pagination': Gov2Pagination,
  'gov2notification': Gov2Notification,
  'gov2search': Gov2Search,
  'gov2formfield': Gov2FormField,
  'gov2button': Gov2Button,
  'gov2tagging': Gov2Tagging,
  'gov2tagged': Gov2Tagged,
  'gov2checkbox': Gov2Checkbox,
  'gov2progress': Gov2Progress,
  'gov2session': Gov2Session,
}

// Kompat global era Vue 2: variabel sloppy `url=` (tanpa deklarasi) di file
// .vue lama legal karena non-strict; modul ESM strict → sediakan binding
// global supaya assignment lama tidak ReferenceError.
if (typeof window.url === 'undefined') window.url = ''

// window.eventBus — kontrak lama dipertahankan ($on/$emit/$off tetap ada).
window.eventBus = eventBus

// Shim drop-in httpVueLoader(url) → async component vue3-sfc-loader.
// Fallback path per-portal sudah built-in di loader (dulu via override
// httpVueLoader.httpRequest di block customVueLoader cubeLayout).
function httpVueLoaderShim(url) {
  return loadVue(url)
}
// Kompat: kode lama boleh set .httpRequest — diabaikan (fallback per-portal
// sudah perilaku default loader baru).
httpVueLoaderShim.httpRequest = null
window.httpVueLoader = httpVueLoaderShim

/**
 * Mount aplikasi halaman hybrid — pengganti `new Vue({ el:'#app', ... })`
 * di layout Twig. Data bridge legacy dipertahankan: vueData/vueMethods/
 * vueCreated/vueWatch di-inject Twig (cubeJS.html) ke dalam `options`;
 * fallback window.__VUE_DATA__/__VUE_METHODS__/__VUE_CREATED__ juga didukung.
 */
function createPageApp(options = {}) {
  const { el, components, ...root } = options

  // Fallback data bridge via window.__VUE_*__ (cara baru yang direkomendasikan)
  if (!root.data && window.__VUE_DATA__) {
    const bridgeData = window.__VUE_DATA__
    root.data = () => bridgeData
  }
  root.methods = Object.assign({}, window.__VUE_METHODS__ || {}, root.methods || {})
  if (!root.created && window.__VUE_CREATED__) {
    root.created = window.__VUE_CREATED__
  }

  const app = createApp(root)

  // Vue 3 default men-strip whitespace antar elemen berbeda dari Vue 2;
  // 'preserve' menjaga parity render template in-DOM dari Twig.
  app.config.compilerOptions.whitespace = 'preserve'

  // Jangan crash satu halaman karena satu komponen error — paritas
  // toleransi Vue 2 + log ke console & notif.
  app.config.errorHandler = (err, instance, info) => {
    console.error('[phpvb]', info, err)
  }

  const register = (name, comp) => {
    app.component(name, comp)
    // Template in-DOM di-lowercase-kan parser HTML; daftarkan alias
    // lowercase untuk tag beruppercase (mis. roleBrowse → rolebrowse).
    const lower = name.toLowerCase()
    if (lower !== name) app.component(lower, comp)
  }

  for (const [name, comp] of Object.entries(coreComponents)) register(name, comp)
  for (const [name, comp] of Object.entries(bvComponents)) register(name, comp)
  if (components) {
    for (const [name, comp] of Object.entries(components)) register(name, comp)
  }

  app.provide('emitter', eventBus)

  const vm = app.mount(el || '#app')
  vm.$app = app
  return vm
}

window.phpvb = {
  version: '3.0.1',
  Vue,
  eventBus,
  loadVue,
  sfcOptions,
  createPageApp,
  components: coreComponents,
}
