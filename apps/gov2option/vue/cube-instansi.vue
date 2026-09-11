<template>
  <div class="h-100 d-flex flex-column">

    <!-- Current selection -->
    <div class="px-3 py-2 border-bottom bg-light" v-if="config.unit_nama">
      <div class="d-flex align-items-center justify-content-between">
        <div class="small">
          <i class="bi bi-building text-primary me-1"></i>
          <strong>{{ config.unit_nama }}</strong>
          <!-- Induk dari unit yang dipilih (mode supervisor), supaya jejak K/L / pemda tidak hilang -->
          <div v-if="ctx.unit && config.portal_nama" class="text-muted" style="font-size:0.7rem">{{ config.portal_nama }}</div>
        </div>
        <button v-if="!config.locked" class="btn btn-sm btn-outline-secondary py-0 px-1"
                @click="resetUnit" title="Reset">
          <i class="bi bi-x-lg small"></i>
        </button>
      </div>
    </div>

    <!-- Badge konteks (mode supervisor saja) -->
    <div v-if="config.supervisor && contextBadge" class="px-3 py-1 border-bottom bg-body-tertiary">
      <span class="badge text-bg-light border fw-normal" style="font-size:0.7rem">
        <i class="bi me-1" :class="contextIcon"></i>{{ contextBadge }}
      </span>
    </div>

    <!-- Lock notice -->
    <div v-if="config.locked" class="px-3 py-2 text-muted small border-bottom">
      <i class="bi bi-lock me-1"></i>Instansi dikunci sesuai role <strong>{{ config.userRole }}</strong>
    </div>

    <!-- Search -->
    <div v-if="!config.locked" class="px-3 py-2 border-bottom">
      <div class="position-relative">
        <input type="text" class="form-control form-control-sm ps-4" v-model="search"
               :placeholder="searchPlaceholder" @input="onSearch">
        <i class="bi bi-search position-absolute" style="left:10px;top:7px;font-size:0.8rem;color:#aaa"></i>
      </div>
    </div>

    <!-- Error alert -->
    <div v-if="errorMsg" class="px-3 py-2">
      <div class="alert alert-danger alert-dismissible mb-0 small">
        <button type="button" class="btn-close btn-close-sm" @click="errorMsg=''"></button>
        <i class="bi bi-exclamation-triangle me-1"></i>{{ errorMsg }}
      </div>
    </div>

    <!-- Content area -->
    <div class="flex-grow-1 overflow-auto" v-if="!config.locked && !errorMsg">

      <!-- Search results -->
      <div v-if="searchMode">
        <div v-if="searchLoading" class="px-3 py-3 text-center text-muted small">
          <div class="spinner-border spinner-border-sm me-1"></div> Mencari...
        </div>
        <ul v-else class="list-group list-group-flush">
          <li v-if="searchResults.length === 0" class="list-group-item border-0 px-3 py-2 text-muted small">
            Tidak ditemukan
          </li>
          <li v-for="item in searchResults" :key="'s-' + itemKey(item)"
              class="list-group-item border-0 px-3 py-2 list-group-item-action"
              :class="{ 'bg-primary-subtle': isSelected(item) }"
              style="cursor:pointer" @click="selectUnit(item)">
            <span class="small" :class="{ 'fw-bold': isSelected(item) }"><strong>{{ item.kode }}</strong> - {{ item.nama }}</span>
          </li>
        </ul>
      </div>

      <!-- Accordion tree: eselon1 → dropdown eselon2 -->
      <div v-else>
        <div v-if="loading" class="px-3 py-3 text-center text-muted small">
          <div class="spinner-border spinner-border-sm me-1"></div> Memuat...
        </div>
        <ul v-else class="list-group list-group-flush">
          <li v-if="treeItems.length === 0" class="list-group-item border-0 px-3 py-2 text-muted small">
            Tidak ada data
          </li>
          <template v-for="item in treeItems" :key="itemKey(item)">
            <!-- Eselon 1 header: chevron = expand, area nama = pilih -->
            <li class="list-group-item border-0 p-0"
                :class="{ 'bg-primary-subtle': isSelected(item) }">
              <div class="d-flex align-items-stretch">
                <button type="button"
                        class="btn btn-sm border-0 rounded-0 px-2 py-2 expand-toggle"
                        :title="expandTitle(item)"
                        @click.stop="toggleExpand(item)">
                  <i class="bi" :class="item.expanded ? 'bi-chevron-down' : 'bi-chevron-right'"
                     style="font-size:0.75rem"></i>
                </button>
                <a href="#"
                   class="flex-grow-1 px-2 py-2 small fw-semibold text-decoration-none select-area"
                   :class="isSelected(item) ? 'fw-bold text-primary' : 'text-body'"
                   :title="selectTitle(item)"
                   @click.prevent="selectUnit(item)">
                  <span>{{ item.kode }}</span> - {{ item.nama }}
                  <i class="bi bi-check2 select-hint ms-1 text-primary"></i>
                </a>
              </div>
            </li>
            <!-- Eselon 2 children (shown when expanded) -->
            <template v-if="item.expanded">
              <li v-if="item.childrenLoading" :key="'l-' + itemKey(item)"
                  class="list-group-item border-0 ps-5 py-1 text-muted small">
                <div class="spinner-border spinner-border-sm me-1"></div> Memuat...
              </li>
              <li v-for="child in item.childrenData" :key="'c-' + itemKey(child)"
                  class="list-group-item border-0 ps-5 py-1 list-group-item-action"
                  :class="{ 'bg-primary-subtle': isSelected(child) }"
                  style="cursor:pointer"
                  @click="selectUnit(child)">
                <span class="small" :class="{ 'fw-bold': isSelected(child) }">{{ child.kode }} - {{ child.nama }}</span>
              </li>
              <li v-if="!item.childrenLoading && item.childrenData && item.childrenData.length === 0"
                  :key="'e-' + itemKey(item)"
                  class="list-group-item border-0 ps-5 py-1 text-muted small">
                {{ emptyChildLabel(item) }}
              </li>
            </template>
          </template>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'cube-instansi',
  data() {
    return {
      // supervisor / ctx / sub_org_term / wilayah_nama = tambahan konteks lintas-portal.
      // Default di bawah = perilaku portal biasa (non-supervisor) → UI identik.
      config: {
        userRole: '', locked: false, unit_nama: '', unit_id: null, parent_id: null, portal: '',
        supervisor: false, sub_org_term: 'OPD', wilayah_nama: '', portal_nama: '',
        ctx: { active: false, scope: '', slug: '', unit: '', unit_id: 0, foreign: false }
      },
      treeItems: [],
      loading: false,
      search: '',
      searchResults: [],
      searchLoading: false,
      searchMode: false,
      searchTimer: null,
      errorMsg: '',
      // Konteks (scope|slug) saat daftar akar terakhir dimuat; berbeda → muat ulang.
      treeLoaded: false,
      treeCtxKey: ''
    }
  },
  computed: {
    ctx() {
      return this.config.ctx || { active: false, scope: '', slug: '', unit: '', unit_id: 0 };
    },
    // Sebutan sub-organisasi; portal biasa tetap memakai kata "instansi".
    orgTerm() {
      return this.config.supervisor ? (this.config.sub_org_term || 'Unit Kerja') : 'instansi';
    },
    searchPlaceholder() {
      return this.config.supervisor ? ('Cari ' + this.orgTerm + '...') : 'Cari instansi...';
    },
    // Konteks daerah → daftar = OPD pemda; selain itu daftar = registry K/L.
    isDaerah() {
      var s = this.ctx.scope;
      return !!this.ctx.slug && (s === 'prov' || s === 'kab' || s === 'kota');
    },
    contextBadge() {
      if (!this.config.supervisor) return '';
      var nama = this.config.portal_nama || this.ctx.slug;
      if (this.isDaerah) {
        return 'OPD — ' + (this.config.wilayah_nama || nama);
      }
      if (this.ctx.scope === 'kl' && this.ctx.slug) {
        return 'Unit Kerja — ' + nama;
      }
      return 'Semua Kementerian/Lembaga';
    },
    contextIcon() {
      return this.isDaerah ? 'bi-geo-alt-fill' : 'bi-bank';
    }
  },
  methods: {
    // Node K/L virtual punya id 0 untuk semua baris → kunci pakai `node`.
    itemKey(item) {
      if (!item) return '';
      return item.node ? item.node : ('id-' + item.id);
    },
    isSelected(item) {
      if (!item) return false;
      if (item.is_portal) {
        return !!(this.ctx.slug && this.ctx.slug === item.slug && !this.ctx.unit);
      }
      return this.config.unit_id == item.id && !!item.id;
    },
    expandTitle(item) {
      if (item && item.is_portal) {
        return item.expanded ? 'Tutup daftar unit kerja' : 'Buka daftar unit kerja';
      }
      return (item && item.expanded) ? 'Tutup sub instansi' : 'Buka sub instansi';
    },
    selectTitle(item) {
      return (item && item.is_portal) ? 'Pilih kementerian/lembaga ini' : 'Pilih instansi ini';
    },
    emptyChildLabel(item) {
      return (item && item.is_portal) ? 'Tidak ada unit kerja' : 'Tidak ada sub instansi';
    },
    // Kunci konteks yang menentukan isi daftar akar (unit tidak ikut: memilih
    // unit hanya mengubah sorotan, bukan daftarnya).
    ctxKey() {
      return (this.ctx.scope || '') + '|' + (this.ctx.slug || '');
    },
    // Config dulu, baru daftar: daftar akar mode supervisor bergantung pada
    // konteks (registry K/L ⇄ OPD pemda ⇄ unit kerja K/L), jadi dimuat ulang
    // setiap konteks berubah — termasuk perubahan dari panel Wilayah atau
    // tautan halaman audit, tanpa perlu memuat ulang browser.
    loadConfig() {
      axios.get('/gov2option/index/getUnitKerjaConfig')
        .then(resp => {
          this.config = resp.data || this.config;
          this.updateTopbar();
          if (!this.treeLoaded || (this.config.supervisor && this.ctxKey() !== this.treeCtxKey)) {
            this.loadTree();
          } else {
            this.autoExpandSelected();
          }
        })
        .catch(e => {
          console.log('instansi config:', e.message);
          if (!this.treeLoaded) this.loadTree();
        });
    },
    loadTree() {
      this.loading = true;
      this.treeCtxKey = this.ctxKey();
      axios.get('/gov2option/index/getUnitKerjaList')
        .then(resp => {
          var items = resp.data || [];
          items.forEach(function(item) {
            item.expanded = false;
            item.childrenData = [];
            item.childrenLoading = false;
          });
          this.treeItems = items;
          this.treeLoaded = true;
          this.loading = false;
          this.autoExpandSelected();
        })
        .catch(e => { this.loading = false; this.handleError(e); });
    },
    toggleExpand(item) {
      if (item.expanded) {
        item.expanded = false;
        return;
      }
      // Collapse all others
      this.treeItems.forEach(function(i) { i.expanded = false; });
      item.expanded = true;
      // Load children if not loaded yet
      if (!item.childrenData || item.childrenData.length === 0) {
        this.loadChildren(item);
      }
    },
    // Node K/L virtual dikirim lewat query string ?node=kl:{slug} (bukan segmen
    // path) supaya route /gov2option/index/{cmd}[/{id}] tidak perlu berubah.
    childrenUrl(item) {
      if (item.is_portal && item.node) {
        return '/gov2option/index/getUnitKerjaList?node=' + encodeURIComponent(item.node);
      }
      var url = '/gov2option/index/getUnitKerjaList/' + item.id;
      if (item.slug) url += '?slug=' + encodeURIComponent(item.slug);
      return url;
    },
    loadChildren(item) {
      item.childrenLoading = true;
      axios.get(this.childrenUrl(item))
        .then(resp => {
          item.childrenData = resp.data || [];
          item.childrenLoading = false;
        })
        .catch(e => {
          item.childrenLoading = false;
          this.handleError(e);
        });
    },
    selectUnit(item) {
      var parentId = item.parent_id || 0;
      var url;
      if (item.is_portal && item.node) {
        url = '/gov2option/index/changePortal'
            + '?node=' + encodeURIComponent(item.node)
            + '&nama=' + encodeURIComponent(item.nama);
      } else {
        url = '/gov2option/index/changePortal/' + item.id
            + '?portal=' + encodeURIComponent(item.portal || '')
            + '&nama=' + encodeURIComponent(item.nama)
            + '&parent_id=' + parentId;
        if (item.slug)  url += '&slug=' + encodeURIComponent(item.slug);
        if (item.scope) url += '&scope=' + encodeURIComponent(item.scope);
      }
      axios.get(url)
        .then(resp => {
          var data = resp.data || {};
          this.config.unit_nama = item.nama;
          this.config.unit_id = item.is_portal ? 0 : item.id;
          this.config.parent_id = item.is_portal ? 0 : parentId;
          this.config.portal = item.portal || '';
          if (data.ctx) this.config.ctx = data.ctx;
          this.search = '';
          this.searchMode = false;
          this.updateTopbar();
          // Pilih K/L → server melepas filter wilayah; topbar ikut dikosongkan.
          if (data.wilayah_cleared) {
            this.config.wilayah_nama = '';
            this.clearWilayahTopbar();
          }
          if (this.config.supervisor) this.loadConfig();
        })
        .catch(e => console.log('changePortal:', e.message));
    },
    resetUnit() {
      axios.get('/gov2option/index/resetPortal')
        .then(resp => {
          var data = resp.data || {};
          this.config.unit_nama = '';
          this.config.unit_id = null;
          this.config.parent_id = null;
          this.config.portal = '';
          if (data.ctx) this.config.ctx = data.ctx;
          this.updateTopbar();
          // Reset = naik satu tingkat (unit → induk, induk K/L → registry);
          // loadConfig memuat ulang daftar bila konteks daftarnya berubah.
          if (this.config.supervisor) this.loadConfig();
        })
        .catch(e => console.log('resetPortal:', e.message));
    },
    onSearch() {
      clearTimeout(this.searchTimer);
      if (!this.search || this.search.length < 2) {
        this.searchMode = false;
        return;
      }
      this.searchMode = true;
      this.searchLoading = true;
      this.searchTimer = setTimeout(() => {
        axios.get('/gov2option/index/searchUnitKerja?q=' + encodeURIComponent(this.search))
          .then(resp => {
            this.searchResults = resp.data || [];
            this.searchLoading = false;
          })
          .catch(e => { this.searchLoading = false; this.handleError(e); });
      }, 300);
    },
    autoExpandSelected() {
      var unitId = this.config.unit_id;
      var parentId = this.config.parent_id;
      var self = this;
      // Root = registry K/L: id semua node 0, sehingga heuristik parent_id di
      // bawah tidak berlaku (dan akan memicu ~99 request). Pakai jalur khusus.
      if (this.treeItems.length > 0 && this.treeItems[0].is_portal) {
        this.autoExpandPortal();
        return;
      }
      if (!unitId || this.treeItems.length === 0) return;
      // If parent_id available, use it directly
      if (parentId) {
        for (var i = 0; i < this.treeItems.length; i++) {
          if (this.treeItems[i].id == parentId) {
            var parent = this.treeItems[i];
            this.treeItems.forEach(function(x) { x.expanded = false; });
            parent.expanded = true;
            if (!parent.childrenData || parent.childrenData.length === 0) {
              this.loadChildren(parent);
            }
            return;
          }
        }
      }
      // Fallback: check if unitId is an eselon1 item
      for (var i = 0; i < this.treeItems.length; i++) {
        if (this.treeItems[i].id == unitId) {
          return;
        }
      }
      // Fallback: search loaded children
      for (var i = 0; i < this.treeItems.length; i++) {
        var item = this.treeItems[i];
        if (item.childrenData && item.childrenData.length > 0) {
          for (var j = 0; j < item.childrenData.length; j++) {
            if (item.childrenData[j].id == unitId) {
              this.treeItems.forEach(function(x) { x.expanded = false; });
              item.expanded = true;
              return;
            }
          }
        }
      }
      // Fallback: load all children to find parent
      var found = false;
      this.treeItems.forEach(function(item) {
        if (!item.childrenData || item.childrenData.length === 0) {
          item.childrenLoading = true;
          axios.get(self.childrenUrl(item))
            .then(function(resp) {
              var children = resp.data || [];
              item.childrenData = children;
              item.childrenLoading = false;
              if (found) return;
              for (var k = 0; k < children.length; k++) {
                if (children[k].id == unitId) {
                  found = true;
                  self.treeItems.forEach(function(x) { x.expanded = false; });
                  item.expanded = true;
                  return;
                }
              }
            })
            .catch(function() { item.childrenLoading = false; });
        }
      });
    },
    // Buka K/L yang sedang jadi konteks (satu request, bukan seluruh registry).
    autoExpandPortal() {
      var slug = this.ctx.slug;
      if (!slug) return;
      for (var i = 0; i < this.treeItems.length; i++) {
        if (this.treeItems[i].slug === slug) {
          var portal = this.treeItems[i];
          this.treeItems.forEach(function(x) { x.expanded = false; });
          portal.expanded = true;
          if (!portal.childrenData || portal.childrenData.length === 0) {
            this.loadChildren(portal);
          }
          return;
        }
      }
    },
    updateTopbar() {
      var el = document.getElementById('topbarUnitKerja');
      if (!el) return;
      var nama = this.config.unit_nama;
      // Unit terpilih → tampilkan induknya juga ("Inspektorat · Kementerian PANRB"),
      // cermin dari markup Twig cubeHeader.html.
      var induk = (this.ctx.unit && this.config.portal_nama) ? this.config.portal_nama : '';
      var icon = nama
        ? '<i class="bi bi-building-fill" style="font-size:1rem;color:#5b4fb9"></i>'
        : '<i class="bi bi-building" style="font-size:1rem"></i>';
      var label = nama
        ? '<span class="text-truncate d-none d-lg-inline ms-1 text-dark" style="max-width:220px;font-size:0.8rem">' + this.escapeHtml(nama)
          + (induk ? '<span class="text-muted ms-1" style="font-size:0.7rem">\u00b7 ' + this.escapeHtml(induk) + '</span>' : '')
          + '</span>'
        : '<span class="d-none d-lg-inline ms-1 text-muted" style="font-size:0.8rem">Instansi</span>';
      el.innerHTML = icon + label;
      // Update tooltip
      el.setAttribute('data-bs-title', nama ? (nama + (induk ? ' \u2014 ' + induk : '')) : 'Pilih Instansi');
      var tooltip = bootstrap.Tooltip.getInstance(el);
      if (tooltip) { tooltip.dispose(); }
      new bootstrap.Tooltip(el);
    },
    // Kembalikan indikator wilayah ke default (tiru gov2wilayah/vue/wilayah.vue).
    clearWilayahTopbar() {
      var el = document.getElementById('topbarWilayah');
      if (el) {
        el.innerHTML = '<i class="bi bi-geo-alt" style="font-size:1rem"></i>'
          + '<span class="d-none d-lg-inline ms-1 text-muted" style="font-size:0.8rem">Wilayah</span>';
      }
      var menu = document.getElementById('topbarWilayahMenu');
      if (menu) {
        menu.innerHTML = '<div class="px-3 py-2 text-muted small">Belum dipilih</div>'
          + '<div class="dropdown-divider my-1"></div>'
          + '<a class="dropdown-item small" href="#" onclick="event.preventDefault(); openSidePanel(\'wilayah\')">'
          + '<i class="bi bi-pencil-square me-2"></i>Pilih Wilayah</a>';
      }
    },
    handleError(e) {
      var msg = 'Terjadi kesalahan';
      if (e.response && e.response.data) {
        var d = e.response.data;
        if (typeof d === 'object' && d.notification) {
          msg = d.notification;
        } else if (typeof d === 'string') {
          msg = d;
        }
      } else if (e.message) {
        msg = e.message;
      }
      this.errorMsg = msg;
    },
    escapeHtml(str) {
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    }
  },
  created() {
    // loadConfig memuat daftar akar setelah config diketahui (lihat loadConfig).
    this.loadConfig();
  },
  mounted() {
    var el = document.getElementById('sidePanelOffcanvas');
    if (el) {
      el.addEventListener('show.bs.offcanvas', () => {
        this.loadConfig();
      });
    }
    // Panel Wilayah (dan penyetel konteks lain) mengumumkan perubahan konteks.
    window.addEventListener('gov2ctx', () => {
      this.loadConfig();
    });
  }
}
</script>

<style scoped>
.list-group-item-action:hover {
  background-color: #f0f0f0;
}
.expand-toggle {
  background-color: transparent;
  color: #6c757d;
  border-right: 1px solid transparent;
  transition: background-color 0.12s, border-color 0.12s;
}
.expand-toggle:hover {
  background-color: #e9ecef;
  border-right-color: #dee2e6;
  color: #212529;
}
.select-area {
  cursor: pointer;
  transition: background-color 0.12s;
}
.select-area:hover {
  background-color: #f0f0f0;
}
.select-hint {
  opacity: 0;
  font-size: 0.75rem;
  transition: opacity 0.12s;
}
.select-area:hover .select-hint {
  opacity: 1;
}
</style>
