<template>
  <div class="h-100 d-flex flex-column">

    <!-- Current selection -->
    <div class="px-3 py-2 border-bottom bg-light" v-if="config.unit_nama">
      <div class="d-flex align-items-center justify-content-between">
        <div class="small">
          <i class="bi bi-building text-primary me-1"></i>
          <strong>{{ config.unit_nama }}</strong>
        </div>
        <button v-if="!config.locked" class="btn btn-sm btn-outline-secondary py-0 px-1"
                @click="resetUnit" title="Reset">
          <i class="bi bi-x-lg small"></i>
        </button>
      </div>
    </div>

    <!-- Lock notice -->
    <div v-if="config.locked" class="px-3 py-2 text-muted small border-bottom">
      <i class="bi bi-lock me-1"></i>Instansi dikunci sesuai role <strong>{{ config.userRole }}</strong>
    </div>

    <!-- Search -->
    <div v-if="!config.locked" class="px-3 py-2 border-bottom">
      <div class="position-relative">
        <input type="text" class="form-control form-control-sm ps-4" v-model="search"
               placeholder="Cari instansi..." @input="onSearch">
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
          <li v-for="item in searchResults" :key="item.id"
              class="list-group-item border-0 px-3 py-2 list-group-item-action"
              :class="{ 'bg-primary-subtle': config.unit_id == item.id }"
              style="cursor:pointer" @click="selectUnit(item)">
            <span class="small" :class="{ 'fw-bold': config.unit_id == item.id }"><strong>{{ item.kode }}</strong> - {{ item.nama }}</span>
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
          <template v-for="item in treeItems">
            <!-- Eselon 1 header: chevron = expand, area nama = pilih -->
            <li :key="'h-' + item.id"
                class="list-group-item border-0 p-0"
                :class="{ 'bg-primary-subtle': config.unit_id == item.id }">
              <div class="d-flex align-items-stretch">
                <button type="button"
                        class="btn btn-sm border-0 rounded-0 px-2 py-2 expand-toggle"
                        :title="item.expanded ? 'Tutup sub instansi' : 'Buka sub instansi'"
                        @click.stop="toggleExpand(item)">
                  <i class="bi" :class="item.expanded ? 'bi-chevron-down' : 'bi-chevron-right'"
                     style="font-size:0.75rem"></i>
                </button>
                <a href="#"
                   class="flex-grow-1 px-2 py-2 small fw-semibold text-decoration-none select-area"
                   :class="config.unit_id == item.id ? 'fw-bold text-primary' : 'text-body'"
                   title="Pilih instansi ini"
                   @click.prevent="selectUnit(item)">
                  <span>{{ item.kode }}</span> - {{ item.nama }}
                  <i class="bi bi-check2 select-hint ms-1 text-primary"></i>
                </a>
              </div>
            </li>
            <!-- Eselon 2 children (shown when expanded) -->
            <template v-if="item.expanded">
              <li v-if="item.childrenLoading" :key="'l-' + item.id"
                  class="list-group-item border-0 ps-5 py-1 text-muted small">
                <div class="spinner-border spinner-border-sm me-1"></div> Memuat...
              </li>
              <li v-for="child in item.childrenData" :key="'c-' + child.id"
                  class="list-group-item border-0 ps-5 py-1 list-group-item-action"
                  :class="{ 'bg-primary-subtle': config.unit_id == child.id }"
                  style="cursor:pointer"
                  @click="selectUnit(child)">
                <span class="small" :class="{ 'fw-bold': config.unit_id == child.id }">{{ child.kode }} - {{ child.nama }}</span>
              </li>
              <li v-if="!item.childrenLoading && item.childrenData && item.childrenData.length === 0"
                  :key="'e-' + item.id"
                  class="list-group-item border-0 ps-5 py-1 text-muted small">
                Tidak ada sub instansi
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
      config: { userRole: '', locked: false, unit_nama: '', unit_id: null, parent_id: null, portal: '' },
      treeItems: [],
      loading: false,
      search: '',
      searchResults: [],
      searchLoading: false,
      searchMode: false,
      searchTimer: null,
      errorMsg: ''
    }
  },
  methods: {
    loadConfig() {
      axios.get('/gov2option/index/getUnitKerjaConfig')
        .then(resp => {
          this.config = resp.data || this.config;
          this.updateTopbar();
          this.autoExpandSelected();
        })
        .catch(e => console.log('instansi config:', e.message));
    },
    loadTree() {
      this.loading = true;
      axios.get('/gov2option/index/getUnitKerjaList')
        .then(resp => {
          var items = resp.data || [];
          items.forEach(function(item) {
            item.expanded = false;
            item.childrenData = [];
            item.childrenLoading = false;
          });
          this.treeItems = items;
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
    loadChildren(item) {
      this.$set(item, 'childrenLoading', true);
      axios.get('/gov2option/index/getUnitKerjaList/' + item.id)
        .then(resp => {
          this.$set(item, 'childrenData', resp.data || []);
          this.$set(item, 'childrenLoading', false);
        })
        .catch(e => {
          this.$set(item, 'childrenLoading', false);
          this.handleError(e);
        });
    },
    selectUnit(item) {
      var parentId = item.parent_id || 0;
      var url = '/gov2option/index/changePortal/' + item.id
              + '?portal=' + encodeURIComponent(item.portal || '')
              + '&nama=' + encodeURIComponent(item.nama)
              + '&parent_id=' + parentId;
      axios.get(url)
        .then(resp => {
          this.config.unit_nama = item.nama;
          this.config.unit_id = item.id;
          this.config.parent_id = parentId;
          this.config.portal = item.portal || '';
          this.search = '';
          this.searchMode = false;
          this.updateTopbar();
        })
        .catch(e => console.log('changePortal:', e.message));
    },
    resetUnit() {
      axios.get('/gov2option/index/resetPortal')
        .then(resp => {
          this.config.unit_nama = '';
          this.config.unit_id = null;
          this.config.portal = '';
          this.updateTopbar();
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
      if (!unitId || this.treeItems.length === 0) return;
      // If parent_id available, use it directly
      if (parentId) {
        for (var i = 0; i < this.treeItems.length; i++) {
          if (this.treeItems[i].id == parentId) {
            var parent = this.treeItems[i];
            this.treeItems.forEach(function(x) { self.$set(x, 'expanded', false); });
            this.$set(parent, 'expanded', true);
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
              this.treeItems.forEach(function(x) { self.$set(x, 'expanded', false); });
              this.$set(item, 'expanded', true);
              return;
            }
          }
        }
      }
      // Fallback: load all children to find parent
      var found = false;
      this.treeItems.forEach(function(item) {
        if (!item.childrenData || item.childrenData.length === 0) {
          self.$set(item, 'childrenLoading', true);
          axios.get('/gov2option/index/getUnitKerjaList/' + item.id)
            .then(function(resp) {
              var children = resp.data || [];
              self.$set(item, 'childrenData', children);
              self.$set(item, 'childrenLoading', false);
              if (found) return;
              for (var k = 0; k < children.length; k++) {
                if (children[k].id == unitId) {
                  found = true;
                  self.treeItems.forEach(function(x) { self.$set(x, 'expanded', false); });
                  self.$set(item, 'expanded', true);
                  return;
                }
              }
            })
            .catch(function() { self.$set(item, 'childrenLoading', false); });
        }
      });
    },
    updateTopbar() {
      var el = document.getElementById('topbarUnitKerja');
      if (!el) return;
      var nama = this.config.unit_nama;
      var icon = nama
        ? '<i class="bi bi-building-fill" style="font-size:1rem;color:#5b4fb9"></i>'
        : '<i class="bi bi-building" style="font-size:1rem"></i>';
      var label = nama
        ? '<span class="text-truncate d-none d-lg-inline ms-1 text-dark" style="max-width:160px;font-size:0.8rem">' + this.escapeHtml(nama) + '</span>'
        : '<span class="d-none d-lg-inline ms-1 text-muted" style="font-size:0.8rem">Instansi</span>';
      el.innerHTML = icon + label;
      // Update tooltip
      el.setAttribute('data-bs-title', nama || 'Pilih Instansi');
      var tooltip = bootstrap.Tooltip.getInstance(el);
      if (tooltip) { tooltip.dispose(); }
      new bootstrap.Tooltip(el);
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
    this.loadConfig();
    this.loadTree();
  },
  mounted() {
    var el = document.getElementById('sidePanelOffcanvas');
    if (el) {
      el.addEventListener('show.bs.offcanvas', () => {
        this.loadConfig();
      });
    }
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
