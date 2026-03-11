<template>
  <div class="h-100 d-flex flex-column">

    <!-- Current selection -->
    <div class="px-3 py-2 border-bottom bg-light" v-if="config.wilayah_nama">
      <div class="d-flex align-items-center justify-content-between">
        <div class="small">
          <i class="bi bi-geo-alt text-success me-1"></i>
          <strong>{{ config.wilayah_nama }}</strong>
          <span v-if="config.wilayah_level" class="badge bg-secondary ms-1">{{ config.wilayah_level }}</span>
        </div>
        <button v-if="!config.locked" class="btn btn-sm btn-outline-secondary py-0 px-1"
                @click="resetWilayah" title="Reset">
          <i class="bi bi-x-lg small"></i>
        </button>
      </div>
    </div>

    <!-- Lock notice -->
    <div v-if="config.locked" class="px-3 py-2 text-muted small border-bottom">
      <i class="bi bi-lock me-1"></i>Wilayah dikunci sesuai role <strong>{{ config.userRole }}</strong>
    </div>

    <!-- Search -->
    <div v-if="!config.locked" class="px-3 py-2 border-bottom">
      <div class="position-relative">
        <input type="text" class="form-control form-control-sm ps-4" v-model="search"
               placeholder="Cari wilayah..." @input="onSearch">
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
              :class="{ 'bg-success-subtle': config.wilayah_id == item.id }"
              style="cursor:pointer" @click="selectWilayah(item)">
            <span class="small" :class="{ 'fw-bold': config.wilayah_id == item.id }">
              {{ item.nama }}
              <span class="badge bg-secondary ms-1">{{ item.level_label }}</span>
            </span>
          </li>
        </ul>
      </div>

      <!-- Breadcrumb navigator (Pilih Wilayah) -->
      <div v-else class="px-3 py-2">

        <!-- Loading -->
        <div v-if="loading" class="py-3 text-center text-muted small">
          <div class="spinner-border spinner-border-sm me-1"></div> Memuat...
        </div>

        <div v-else>
          <!-- Breadcrumb path -->
          <div v-for="(path, idx) in pathData" :key="'p-'+idx" class="mb-2" v-if="path.level <= maxLevel">
            <div class="input-group input-group-sm">
              <span class="input-group-text" style="min-width:90px;font-size:0.75rem">{{ levelName(path.level_label, path.level) }}</span>
              <input type="text" class="form-control" :value="path.caption || path.nama" readonly
                     @click="goBack(path.id)" style="cursor:pointer;font-size:0.8rem"
                     :class="{ 'bg-success-subtle fw-bold': config.wilayah_id == path.id }">
              <button class="btn btn-outline-primary" type="button" @click="goBack(path.id)" title="Kembali">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>
          </div>

          <!-- Next level dropdown -->
          <div v-if="pathData.length > 0 && pathData[pathData.length-1].level < maxLevel" class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text" style="min-width:90px;font-size:0.75rem">{{ nextLevelName }}</span>
              <select class="form-select" v-model="selectedId" @change="drillDown" style="font-size:0.8rem">
                <option value="" disabled>Pilih...</option>
                <option v-for="item in childList" :key="item.id" :value="item.id">{{ item.nama }}</option>
              </select>
              <span class="input-group-text" style="font-size:0.75rem">{{ childList.length }}</span>
            </div>
          </div>

          <!-- Select button (when at a selectable level) -->
          <div v-if="pathData.length > 0 && lastPath" class="mt-3">
            <button class="btn btn-sm btn-success w-100" @click="selectFromPath(lastPath)"
                    :disabled="config.wilayah_id == lastPath.id">
              <i class="bi bi-check-lg me-1"></i>
              Pilih: {{ lastPath.caption || lastPath.nama }} ({{ lastPath.level_label }})
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'cube-wilayah',
  data() {
    return {
      config: { userRole: '', locked: false, wilayah_nama: '', wilayah_id: null, wilayah_level: '', wilayah_parent_id: null },
      pathData: [],
      childList: [],
      selectedId: '',
      loading: false,
      maxLevel: 4,
      search: '',
      searchResults: [],
      searchLoading: false,
      searchMode: false,
      searchTimer: null,
      errorMsg: ''
    }
  },
  computed: {
    lastPath() {
      if (this.pathData.length === 0) return null;
      var last = this.pathData[this.pathData.length - 1];
      // Selectable if level >= 2 (kabupaten or deeper)
      return last.level >= 2 ? last : null;
    },
    nextLevelName() {
      if (this.childList.length > 0 && this.childList[0].level_label) {
        var label = this.childList[0].level_label.toUpperCase();
        return label === 'KELURAHAN' ? 'KEL/DESA' : label;
      }
      return 'PILIH';
    }
  },
  methods: {
    levelName(label, level) {
      if (!label) return '';
      if (level == 0) return 'NASIONAL';
      var name = label.toUpperCase();
      return name === 'KELURAHAN' ? 'KEL/DESA' : name;
    },
    loadConfig() {
      axios.get('/gov2wilayah/sidepanel/getWilayahConfig')
        .then(resp => {
          this.config = resp.data || this.config;
          this.updateTopbar();
        })
        .catch(e => console.log('wilayah config:', e.message));
    },
    loadBreadcrumb(id) {
      this.loading = true;
      var url = '/gov2wilayah/sidepanel/breadcrumb';
      if (id !== undefined && id !== null) url += '/' + id;
      axios.get(url)
        .then(resp => {
          var data = resp.data;
          if (data) {
            this.pathData = Array.from(Object.keys(data), function(k) { return data[k]; });
          }
          this.loading = false;
          this.loadChildren();
        })
        .catch(e => { this.loading = false; this.handleError(e); });
    },
    loadChildren() {
      if (this.pathData.length === 0) return;
      var lastLevel = this.pathData[this.pathData.length - 1].level;
      if (lastLevel >= this.maxLevel) {
        this.childList = [];
        return;
      }
      axios.get('/gov2wilayah/sidepanel/listWilayah/1/-1')
        .then(resp => {
          var data = resp.data;
          this.childList = Array.from(Object.keys(data), function(k) { return data[k]; });
        })
        .catch(e => this.handleError(e));
    },
    drillDown() {
      if (!this.selectedId) return;
      var id = this.selectedId;
      this.selectedId = '';
      this.childList = [];
      this.loadBreadcrumb(id);
    },
    goBack(id) {
      if (!id || id <= 0) {
        this.loadBreadcrumb(-2);
      } else {
        // Go to parent of this level
        var idx = -1;
        for (var i = 0; i < this.pathData.length; i++) {
          if (this.pathData[i].id == id) { idx = i; break; }
        }
        if (idx > 0) {
          this.loadBreadcrumb(this.pathData[idx - 1].id);
        } else {
          this.loadBreadcrumb(-2);
        }
      }
    },
    selectWilayah(item) {
      var parentId = item.parent_id || 0;
      var url = '/gov2wilayah/sidepanel/changeWilayah/' + item.id
              + '?nama=' + encodeURIComponent(item.nama)
              + '&level=' + encodeURIComponent(item.level_label || '')
              + '&parent_id=' + parentId;
      axios.get(url)
        .then(resp => {
          this.config.wilayah_nama = item.nama;
          this.config.wilayah_id = item.id;
          this.config.wilayah_level = item.level_label || '';
          this.config.wilayah_parent_id = parentId;
          this.search = '';
          this.searchMode = false;
          this.updateTopbar();
        })
        .catch(e => console.log('changeWilayah:', e.message));
    },
    selectFromPath(path) {
      this.selectWilayah({
        id: path.id,
        nama: path.caption || path.nama,
        level_label: path.level_label,
        parent_id: path.parent_id || 0
      });
    },
    resetWilayah() {
      axios.get('/gov2wilayah/sidepanel/resetWilayah')
        .then(resp => {
          this.config.wilayah_nama = '';
          this.config.wilayah_id = null;
          this.config.wilayah_level = '';
          this.config.wilayah_parent_id = null;
          this.updateTopbar();
        })
        .catch(e => console.log('resetWilayah:', e.message));
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
        axios.get('/gov2wilayah/sidepanel/searchWilayah?q=' + encodeURIComponent(this.search))
          .then(resp => {
            this.searchResults = resp.data || [];
            this.searchLoading = false;
          })
          .catch(e => { this.searchLoading = false; this.handleError(e); });
      }, 300);
    },
    updateTopbar() {
      var el = document.getElementById('topbarWilayah');
      if (el) {
        var nama = this.config.wilayah_nama;
        if (nama) {
          el.innerHTML = '<i class="bi bi-geo-alt-fill fs-5"></i>' +
            '<span class="text-truncate d-none d-lg-inline ms-2" style="max-width:180px">' + this.escapeHtml(nama) + '</span>';
          el.classList.remove('text-muted');
          el.classList.add('text-body');
          el.setAttribute('data-bs-title', nama);
          var tip = bootstrap.Tooltip.getInstance(el);
          if (tip) { tip.dispose(); }
          new bootstrap.Tooltip(el);
        } else {
          el.innerHTML = '<i class="bi bi-geo-alt fs-5"></i><span class="text-muted d-none d-lg-inline ms-2">Pilih Wilayah</span>';
          el.classList.add('text-muted');
          el.classList.remove('text-body');
          el.setAttribute('data-bs-title', 'Pilih Wilayah');
          var tip = bootstrap.Tooltip.getInstance(el);
          if (tip) { tip.dispose(); }
          new bootstrap.Tooltip(el);
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
    this.loadBreadcrumb(-1);
  },
  mounted() {
    var el = document.getElementById('sidePanelOffcanvas');
    if (el) {
      el.addEventListener('show.bs.offcanvas', () => {
        var wilayahPanel = document.getElementById('sidePanel-wilayah');
        if (wilayahPanel && wilayahPanel.style.display !== 'none') {
          this.loadConfig();
        }
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
