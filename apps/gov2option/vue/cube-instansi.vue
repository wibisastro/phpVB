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
            <!-- Eselon 1 header (clickable to expand/collapse) -->
            <li :key="'h-' + item.id"
                class="list-group-item border-0 px-3 py-2 list-group-item-action"
                style="cursor:pointer"
                @click="toggleExpand(item)">
              <div class="d-flex align-items-center">
                <i class="bi me-2" :class="item.expanded ? 'bi-chevron-down' : 'bi-chevron-right'"
                   style="font-size:0.75rem; width:12px"></i>
                <div class="flex-grow-1 small fw-semibold">
                  <span>{{ item.kode }}</span> - {{ item.nama }}
                </div>
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
      if (!this.config.unit_id || this.treeItems.length === 0) return;
      var parentId = this.config.parent_id;
      var self = this;
      // If no parent_id, selected is eselon1 itself — no expand needed
      if (!parentId) return;
      // Find the parent eselon1 item and expand it
      for (var i = 0; i < this.treeItems.length; i++) {
        if (this.treeItems[i].id == parentId) {
          var parent = this.treeItems[i];
          // Collapse all, expand this parent
          this.treeItems.forEach(function(x) { self.$set(x, 'expanded', false); });
          this.$set(parent, 'expanded', true);
          // Load children if not yet loaded
          if (!parent.childrenData || parent.childrenData.length === 0) {
            this.loadChildren(parent);
          }
          return;
        }
      }
    },
    updateTopbar() {
      var el = document.getElementById('topbarUnitKerja');
      if (el) {
        var nama = this.config.unit_nama;
        if (nama) {
          el.innerHTML = '<i class="bi bi-building fs-5 me-2"></i>' +
            '<span class="text-truncate" style="max-width:200px">' + this.escapeHtml(nama) + '</span>';
          el.classList.remove('text-muted');
          el.classList.add('text-body');
          el.title = nama;
        } else {
          el.innerHTML = '<i class="bi bi-building fs-5 me-2"></i><span class="text-muted">Pilih Instansi</span>';
          el.classList.add('text-muted');
          el.classList.remove('text-body');
          el.title = '';
        }
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
</style>
