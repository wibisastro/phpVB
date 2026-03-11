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

    <!-- Error alert -->
    <div v-if="errorMsg" class="px-3 py-2">
      <div class="alert alert-danger alert-dismissible mb-0 small">
        <button type="button" class="btn-close btn-close-sm" @click="errorMsg=''"></button>
        <i class="bi bi-exclamation-triangle me-1"></i>{{ errorMsg }}
      </div>
    </div>

    <!-- Content area -->
    <div class="flex-grow-1 overflow-auto" v-if="!config.locked && !errorMsg">
      <div class="px-3 py-2">

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

          <!-- Next level dropdown + filter -->
          <div v-if="pathData.length > 0 && pathData[pathData.length-1].level < maxLevel" class="mb-2">
            <!-- Filter input -->
            <div class="position-relative mb-1" v-if="childList.length > 10">
              <input type="text" class="form-control form-control-sm ps-4" v-model="filter"
                     :placeholder="'Filter ' + nextLevelName.toLowerCase() + '...'">
              <i class="bi bi-funnel position-absolute" style="left:10px;top:7px;font-size:0.75rem;color:#aaa"></i>
            </div>
            <div class="input-group input-group-sm">
              <span class="input-group-text" style="min-width:90px;font-size:0.75rem">{{ nextLevelName }}</span>
              <select class="form-select" v-model="selectedId" @change="drillDown" style="font-size:0.8rem">
                <option value="" disabled>Pilih...</option>
                <option v-for="item in filteredChildList" :key="item.id" :value="item.id">{{ item.nama }}</option>
              </select>
              <span class="input-group-text" style="font-size:0.75rem">{{ filteredChildList.length }}</span>
            </div>
          </div>

          <!-- Select button (selectable from level 1 / provinsi) -->
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
      filter: '',
      loading: false,
      maxLevel: 4,
      errorMsg: ''
    }
  },
  computed: {
    lastPath() {
      if (this.pathData.length === 0) return null;
      var last = this.pathData[this.pathData.length - 1];
      // Selectable from level 1 (provinsi) and deeper
      return last.level >= 1 ? last : null;
    },
    nextLevelName() {
      if (this.childList.length > 0 && this.childList[0].level_label) {
        var label = this.childList[0].level_label.toUpperCase();
        return label === 'KELURAHAN' ? 'KEL/DESA' : label;
      }
      return 'PILIH';
    },
    filteredChildList() {
      if (!this.filter || this.filter.length < 1) return this.childList;
      var q = this.filter.toLowerCase();
      return this.childList.filter(function(item) {
        return item.nama.toLowerCase().indexOf(q) !== -1;
      });
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
      this.filter = '';
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
          this.pathData = [];
          this.childList = [];
          this.loadBreadcrumb(-1);
          this.updateTopbar();
        })
        .catch(e => console.log('resetWilayah:', e.message));
    },
    updateTopbar() {
      // Update trigger: show selected name or default "Wilayah"
      var el = document.getElementById('topbarWilayah');
      if (el) {
        var nama = this.config.wilayah_nama;
        var icon = nama
          ? '<i class="bi bi-geo-alt-fill" style="font-size:1rem;color:#5b4fb9"></i>'
          : '<i class="bi bi-geo-alt" style="font-size:1rem"></i>';
        var label = nama
          ? '<span class="text-truncate d-none d-lg-inline ms-1 text-dark" style="max-width:140px;font-size:0.8rem">' + this.escapeHtml(nama) + '</span>'
          : '<span class="d-none d-lg-inline ms-1 text-muted" style="font-size:0.8rem">Wilayah</span>';
        el.innerHTML = icon + label;
      }
      // Update dropdown menu content with level breakdown
      var menu = document.getElementById('topbarWilayahMenu');
      if (menu) {
        var html = '';
        if (this.pathData.length > 0 && this.config.wilayah_nama) {
          // Build level breakdown from pathData
          for (var i = 0; i < this.pathData.length; i++) {
            var p = this.pathData[i];
            if (p.level == 0) continue; // skip NASIONAL
            var lbl = (p.level_label || '').charAt(0).toUpperCase() + (p.level_label || '').slice(1);
            if (lbl === 'Kelurahan') lbl = 'Kel/Desa';
            var isBold = (p.id == this.config.wilayah_id) ? ' fw-bold' : '';
            html += '<div class="px-3 py-1 small' + isBold + '">' +
              '<span class="text-muted" style="display:inline-block;min-width:65px">' + this.escapeHtml(lbl) + '</span> ' +
              this.escapeHtml(p.caption || p.nama) + '</div>';
          }
        } else {
          html = '<div class="px-3 py-2 text-muted small">Belum dipilih</div>';
        }
        html += '<div class="dropdown-divider my-1"></div>';
        html += '<a class="dropdown-item small" href="#" onclick="event.preventDefault(); openSidePanel(\'wilayah\')">' +
          '<i class="bi bi-pencil-square me-2"></i>Pilih Wilayah</a>';
        menu.innerHTML = html;
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
    var self = this;
    var el = document.getElementById('sidePanelOffcanvas');
    if (el) {
      el.addEventListener('show.bs.offcanvas', function() {
        var wilayahPanel = document.getElementById('sidePanel-wilayah');
        if (wilayahPanel && wilayahPanel.style.display !== 'none') {
          self.loadConfig();
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
