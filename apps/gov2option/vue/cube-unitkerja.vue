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
      <i class="bi bi-lock me-1"></i>Unit kerja dikunci sesuai role <strong>{{ config.userRole }}</strong>
    </div>

    <!-- Search -->
    <div v-if="!config.locked" class="px-3 py-2 border-bottom">
      <div class="position-relative">
        <input type="text" class="form-control form-control-sm ps-4" v-model="search"
               placeholder="Cari unit kerja..." @input="onSearch">
        <i class="bi bi-search position-absolute" style="left:10px;top:7px;font-size:0.8rem;color:#aaa"></i>
      </div>
    </div>

    <!-- Content area -->
    <div class="flex-grow-1 overflow-auto" v-if="!config.locked">

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
              style="cursor:pointer" @click="selectUnit(item)">
            <span class="small"><strong>{{ item.kode }}</strong> - {{ item.nama }}</span>
          </li>
        </ul>
      </div>

      <!-- Tree view -->
      <div v-else>
        <!-- Breadcrumb navigation -->
        <div v-if="breadcrumb.length > 0" class="px-3 py-1 border-bottom bg-light">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
              <li class="breadcrumb-item">
                <a href="#" @click.prevent="drillTo(-1)" class="text-decoration-none">
                  <i class="bi bi-house-door"></i>
                </a>
              </li>
              <li v-for="(bc, idx) in breadcrumb" :key="bc.id"
                  class="breadcrumb-item" :class="{ active: idx === breadcrumb.length - 1 }">
                <a v-if="idx < breadcrumb.length - 1" href="#" @click.prevent="drillTo(idx)"
                   class="text-decoration-none">{{ bc.kode }}</a>
                <span v-else>{{ bc.kode }}</span>
              </li>
            </ol>
          </nav>
        </div>

        <!-- Tree items -->
        <div v-if="loading" class="px-3 py-3 text-center text-muted small">
          <div class="spinner-border spinner-border-sm me-1"></div> Memuat...
        </div>
        <ul v-else class="list-group list-group-flush">
          <li v-if="treeItems.length === 0" class="list-group-item border-0 px-3 py-2 text-muted small">
            Tidak ada data
          </li>
          <li v-for="item in treeItems" :key="item.id"
              class="list-group-item border-0 px-3 py-1 list-group-item-action">
            <div class="d-flex align-items-center" style="min-height:32px">
              <div class="flex-grow-1 small" style="cursor:pointer" @click="selectUnit(item)">
                <strong>{{ item.kode }}</strong> - {{ item.nama }}
              </div>
              <button v-if="item.has_children" class="btn btn-sm text-muted py-0 px-1"
                      @click.stop="drillDown(item)" title="Lihat sub unit">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'cube-unitkerja',
  data() {
    return {
      config: { userRole: '', locked: false, unit_nama: '', unit_id: null, portal: '' },
      treeItems: [],
      breadcrumb: [],
      loading: false,
      search: '',
      searchResults: [],
      searchLoading: false,
      searchMode: false,
      searchTimer: null
    }
  },
  methods: {
    loadConfig() {
      axios.get('/gov2option/index/getUnitKerjaConfig')
        .then(resp => {
          this.config = resp.data || this.config;
          this.updateTopbar();
        })
        .catch(e => console.log('unitkerja config:', e.message));
    },
    loadTree(parentId) {
      this.loading = true;
      var url = '/gov2option/index/getUnitKerjaList' + (parentId ? '/' + parentId : '');
      axios.get(url)
        .then(resp => {
          this.treeItems = resp.data || [];
          this.loading = false;
        })
        .catch(e => { this.loading = false; console.log('unitkerja list:', e.message); });
    },
    drillDown(item) {
      this.breadcrumb.push({ id: item.id, kode: item.kode, nama: item.nama });
      this.loadTree(item.id);
    },
    drillTo(index) {
      if (index < 0) {
        this.breadcrumb = [];
        this.loadTree(0);
      } else {
        var target = this.breadcrumb[index];
        this.breadcrumb = this.breadcrumb.slice(0, index + 1);
        this.loadTree(target.id);
      }
    },
    selectUnit(item) {
      var url = '/gov2option/index/changePortal/' + item.id
              + '?portal=' + encodeURIComponent(item.portal || '')
              + '&nama=' + encodeURIComponent(item.nama);
      axios.get(url)
        .then(resp => {
          this.config.unit_nama = item.nama;
          this.config.unit_id = item.id;
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
          .catch(e => { this.searchLoading = false; });
      }, 300);
    },
    updateTopbar() {
      // Update topbar unit kerja display
      var el = document.getElementById('topbarUnitKerja');
      if (el) {
        var nama = this.config.unit_nama;
        if (nama) {
          el.innerHTML = '<i class="bi bi-building me-1"></i>' +
            '<span class="text-truncate" style="max-width:200px">' + this.escapeHtml(nama) + '</span>';
          el.classList.remove('text-muted');
          el.classList.add('text-body');
        } else {
          el.innerHTML = '<i class="bi bi-building me-1"></i><span class="text-muted">Pilih Unit Kerja</span>';
          el.classList.add('text-muted');
          el.classList.remove('text-body');
        }
      }
    },
    escapeHtml(str) {
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    }
  },
  created() {
    this.loadConfig();
    this.loadTree(0);
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
