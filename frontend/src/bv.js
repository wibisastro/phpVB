// Wrapper komponen b-* di atas Bootstrap 5 NATIVE (keputusan #6118:
// bootstrap-vue TIDAK diganti bootstrap-vue-next). API = subset yang
// benar-benar dipakai file .vue repo (tablepack, gov2formfield-bs4,
// gov2option controlpanel/option, survey-result).
//
// Mengandalkan global `bootstrap` (public/cube/js/bootstrap.bundle.min.js).
import { h } from 'vue'
import eventBus from './eventBus.js'

const bs = () => (typeof window !== 'undefined' ? window.bootstrap : undefined)

const BButton = {
  name: 'BButton',
  props: {
    variant: { type: String, default: 'secondary' },
    size: String,
    disabled: Boolean,
    type: { type: String, default: 'button' },
    block: Boolean,
  },
  emits: ['click'],
  template: `
    <button :type="type" class="btn" :disabled="disabled"
      :class="['btn-' + variant, size ? 'btn-' + size : '', block ? 'd-block w-100' : '']"
      @click="$emit('click', $event)">
      <slot></slot>
    </button>`,
}

const BSpinner = {
  name: 'BSpinner',
  props: { variant: String, small: Boolean, label: { type: String, default: 'Loading...' }, type: { type: String, default: 'border' } },
  template: `
    <span class="spinner-border" :class="[variant ? 'text-' + variant : '', small ? 'spinner-border-sm' : '']" role="status">
      <span class="visually-hidden">{{ label }}</span>
    </span>`,
}

const BBadge = {
  name: 'BBadge',
  props: { variant: { type: String, default: 'secondary' }, pill: Boolean },
  template: `<span class="badge" :class="['bg-' + variant, pill ? 'rounded-pill' : '', variant === 'warning' || variant === 'light' ? 'text-dark' : '']"><slot></slot></span>`,
}

const BRow = { name: 'BRow', template: `<div class="row"><slot></slot></div>` }

const BCol = {
  name: 'BCol',
  props: { cols: [String, Number], md: [String, Number], lg: [String, Number], sm: [String, Number] },
  computed: {
    colClass() {
      const cls = []
      if (this.cols) cls.push(`col-${this.cols}`)
      if (this.sm) cls.push(`col-sm-${this.sm}`)
      if (this.md) cls.push(`col-md-${this.md}`)
      if (this.lg) cls.push(`col-lg-${this.lg}`)
      if (!cls.length) cls.push('col')
      return cls
    },
  },
  template: `<div :class="colClass"><slot></slot></div>`,
}

const BCard = {
  name: 'BCard',
  props: { noBody: Boolean },
  template: `<div class="card"><template v-if="noBody"><slot></slot></template><div v-else class="card-body"><slot></slot></div></div>`,
}

const BCardHeader = {
  name: 'BCardHeader',
  props: { headerTag: { type: String, default: 'div' } },
  template: `<component :is="headerTag" class="card-header"><slot></slot></component>`,
}

// b-collapse: dukung id + accordion + toggle via event bus
// 'bv::toggle::collapse' (paritas bootstrap-vue; emitter di app di-patch
// dari this.$root.$emit ke eventBus.$emit).
const BCollapse = {
  name: 'BCollapse',
  props: { id: String, visible: Boolean, accordion: String },
  data() {
    return { shown: this.visible }
  },
  created() {
    this._onToggle = (targetId) => {
      if (targetId !== this.id) {
        if (this.accordion && this.shown) this.shown = false
        return
      }
      this.shown = !this.shown
    }
    eventBus.on('bv::toggle::collapse', this._onToggle)
  },
  unmounted() {
    eventBus.off('bv::toggle::collapse', this._onToggle)
  },
  template: `<div class="collapse" :class="{ show: shown }" :id="id"><slot></slot></div>`,
}

const BDropdown = {
  name: 'BDropdown',
  props: { variant: { type: String, default: 'secondary' }, size: String, right: Boolean, text: String, id: String },
  template: `
    <div class="dropdown d-inline-block">
      <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
        :class="['btn-' + variant, size ? 'btn-' + size : '']">
        <slot name="button-content">{{ text }}</slot>
      </button>
      <ul class="dropdown-menu" :class="{ 'dropdown-menu-end': right }">
        <slot></slot>
      </ul>
    </div>`,
}

const BDropdownItem = {
  name: 'BDropdownItem',
  props: { href: String, disabled: Boolean },
  emits: ['click'],
  template: `
    <li><a class="dropdown-item" :class="{ disabled: disabled }" :href="href || '#'"
      @click.prevent="!disabled && $emit('click', $event)"><slot></slot></a></li>`,
}

const BDropdownDivider = { name: 'BDropdownDivider', template: `<li><hr class="dropdown-divider"></li>` }

// b-form-checkbox (paritas bootstrap-vue): tanpa `value` → boolean;
// dengan `value` → checked = value, unchecked = uncheckedValue (false).
const BFormCheckbox = {
  name: 'BFormCheckbox',
  props: {
    modelValue: { default: false },
    value: { default: true },
    uncheckedValue: { default: false },
    disabled: Boolean,
  },
  emits: ['update:modelValue', 'change'],
  computed: {
    isChecked() {
      if (Array.isArray(this.modelValue)) return this.modelValue.includes(this.value)
      return this.modelValue === this.value || this.modelValue === true
    },
  },
  methods: {
    onChange(evt) {
      let next
      if (Array.isArray(this.modelValue)) {
        next = this.modelValue.slice()
        if (evt.target.checked) { if (!next.includes(this.value)) next.push(this.value) }
        else { next = next.filter((v) => v !== this.value) }
      } else {
        next = evt.target.checked ? this.value : this.uncheckedValue
      }
      this.$emit('update:modelValue', next)
      this.$emit('change', next)
    },
  },
  template: `
    <div class="form-check">
      <input class="form-check-input" type="checkbox" :checked="isChecked" :disabled="disabled" @change="onChange">
      <label class="form-check-label" v-if="$slots.default"><slot></slot></label>
    </div>`,
}

// b-form-select: options = array of scalar | {value,text} | {value,html}
const BFormSelect = {
  name: 'BFormSelect',
  props: { modelValue: {}, options: { type: Array, default: () => [] }, disabled: Boolean },
  emits: ['update:modelValue', 'change'],
  computed: {
    normalized() {
      return this.options.map((opt) =>
        opt && typeof opt === 'object' ? { value: opt.value, text: opt.text ?? opt.html ?? String(opt.value) } : { value: opt, text: String(opt) }
      )
    },
  },
  methods: {
    onChange(evt) {
      const idx = evt.target.selectedIndex
      const val = this.normalized[idx] ? this.normalized[idx].value : evt.target.value
      this.$emit('update:modelValue', val)
      this.$emit('change', val)
    },
  },
  template: `
    <select class="form-select" :disabled="disabled" @change="onChange">
      <option v-for="opt in normalized" :value="opt.value" :selected="opt.value === modelValue">{{ opt.text }}</option>
    </select>`,
}

const BFormTextarea = {
  name: 'BFormTextarea',
  props: { modelValue: String, rows: [String, Number], placeholder: String, disabled: Boolean },
  emits: ['update:modelValue'],
  template: `<textarea class="form-control" :rows="rows" :placeholder="placeholder" :disabled="disabled"
    :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"></textarea>`,
}

const BModal = {
  name: 'BModal',
  props: { modelValue: Boolean, title: String, hideFooter: Boolean, id: String, size: String },
  emits: ['update:modelValue', 'ok', 'hidden', 'shown'],
  watch: {
    modelValue(val) { val ? this.show() : this.hide() },
  },
  mounted() {
    const B = bs()
    if (B) {
      this._modal = new B.Modal(this.$refs.el, { backdrop: true })
      this.$refs.el.addEventListener('hidden.bs.modal', () => {
        this.$emit('update:modelValue', false)
        this.$emit('hidden')
      })
      this.$refs.el.addEventListener('shown.bs.modal', () => this.$emit('shown'))
      if (this.modelValue) this.show()
    }
  },
  unmounted() {
    if (this._modal) this._modal.dispose()
  },
  methods: {
    show() { this._modal && this._modal.show() },
    hide() { this._modal && this._modal.hide() },
  },
  template: `
    <div class="modal fade" tabindex="-1" :id="id" ref="el">
      <div class="modal-dialog" :class="size ? 'modal-' + size : ''">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ title }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body"><slot></slot></div>
          <div class="modal-footer" v-if="!hideFooter">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-primary" @click="$emit('ok')">OK</button>
          </div>
        </div>
      </div>
    </div>`,
}

const BOverlay = {
  name: 'BOverlay',
  props: { show: Boolean },
  template: `
    <div class="position-relative">
      <slot></slot>
      <div v-if="show" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
        style="background: rgba(255,255,255,.65); z-index: 10;">
        <span class="spinner-border text-info" role="status"><span class="visually-hidden">Memuat...</span></span>
      </div>
    </div>`,
}

const BTooltip = {
  name: 'BTooltip',
  props: { target: String, triggers: { type: String, default: 'hover' } },
  mounted() {
    this.$nextTick(() => {
      const B = bs()
      const el = document.getElementById(this.target)
      if (B && el) {
        this._tip = new B.Tooltip(el, {
          title: () => (this.$refs.content ? this.$refs.content.textContent.trim() : ''),
          trigger: this.triggers,
        })
      }
    })
  },
  unmounted() {
    if (this._tip) this._tip.dispose()
  },
  render() {
    return h('div', { ref: 'content', style: { display: 'none' } }, this.$slots.default ? this.$slots.default() : [])
  },
}

export default {
  'b-button': BButton,
  'b-spinner': BSpinner,
  'b-badge': BBadge,
  'b-row': BRow,
  'b-col': BCol,
  'b-card': BCard,
  'b-card-header': BCardHeader,
  'b-collapse': BCollapse,
  'b-dropdown': BDropdown,
  'b-dropdown-item': BDropdownItem,
  'b-dropdown-divider': BDropdownDivider,
  'b-form-checkbox': BFormCheckbox,
  'b-form-select': BFormSelect,
  'b-form-textarea': BFormTextarea,
  'b-modal': BModal,
  'b-overlay': BOverlay,
  'b-tooltip': BTooltip,
}
