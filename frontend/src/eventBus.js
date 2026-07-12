import mitt from 'mitt'

// Event bus global pengganti `new Vue()` (Vue 2).
// API mitt: on/off/emit. Alias $on/$off/$emit/$once dipertahankan supaya
// seluruh kode lama (eventBus.$on / eventBus.$emit) jalan tanpa perubahan.
const emitter = mitt()

emitter.$on = (type, handler) => emitter.on(type, handler)
emitter.$off = (type, handler) => emitter.off(type, handler)
emitter.$emit = (type, ...args) => emitter.emit(type, args.length > 1 ? args : args[0])
emitter.$once = (type, handler) => {
  const wrapped = (evt) => {
    emitter.off(type, wrapped)
    handler(evt)
  }
  emitter.on(type, wrapped)
}

export default emitter
