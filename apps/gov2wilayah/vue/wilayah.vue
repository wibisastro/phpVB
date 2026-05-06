<template>
  <div class="h-100 d-flex flex-column">

    <!-- Current selection -->
    <div class="px-3 py-2 border-bottom bg-primary-subtle" v-if="config.wilayah_nama">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center small">
          <div class="icon-box sm bg-primary text-white rounded-circle me-2" style="width:28px;height:28px;font-size:0.75rem">
            <i class="bi bi-geo-alt-fill"></i>
          </div>
          <div>
            <strong>{{ config.wilayah_nama }}</strong>
            <span v-if="config.wilayah_level" class="badge bg-primary ms-1" style="font-size:0.65rem">{{ config.wilayah_level }}</span>
          </div>
        </div>
        <button v-if="!config.locked" class="btn btn-sm btn-outline-danger py-0 px-1"
                @click="resetWilayah" title="Reset">
          <i class="bi bi-x-lg small"></i>
        </button>
      </div>
    </div>

    <!-- Lock notice -->
    <div v-if="config.locked" class="px-3 py-2 text-muted small border-bottom bg-light">
      <i class="bi bi-lock-fill me-1"></i>Wilayah dikunci sesuai role <strong>{{ config.userRole }}</strong>
    </div>

    <!-- Error alert -->
    <div v-if="errorMsg" class="px-3 py-2">
      <div class="alert alert-danger alert-dismissible mb-0 small">
        <button type="button" class="btn-close btn-close-sm" @click="errorMsg=''"></button>
        <i class="bi bi-exclamation-triangle me-1"></i>{{ errorMsg }}
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading && !config.locked && !errorMsg" class="flex-grow-1 py-4 text-center text-muted">
      <div class="spinner-border spinner-border-sm text-primary me-1"></div>
      <span class="small">Memuat data...</span>
    </div>

    <template v-else-if="!config.locked && !errorMsg">

      <!-- Breadcrumb path -->
      <div class="list-group list-group-flush flex-shrink-0">
        <template v-for="(path, idx) in pathData">
          <a v-if="path.level <= maxLevel && (path.level > 0 || hasMultipleRoots)" :key="'p-'+idx" href="#"
             class="list-group-item list-group-item-action d-flex align-items-center py-2"
             :class="{ 'bg-success-subtle': config.wilayah_id == path.id }"
             @click.prevent="goBack(path.id)">
            <span class="badge bg-body-secondary text-muted me-2" style="min-width:70px;font-size:0.7rem">{{ levelName(path.level_label, path.level) }}</span>
            <span class="small" :class="{ 'fw-bold text-success': config.wilayah_id == path.id }">{{ path.caption || path.nama }}</span>
            <i class="bi bi-arrow-counterclockwise ms-auto text-muted small"></i>
          </a>
        </template>
      </div>

      <!-- Search input -->
      <div v-if="pathData.length > 0 && pathData[pathData.length-1].level < maxLevel && childList.length > 0"
           class="border-top flex-shrink-0 px-3 pt-2 pb-1">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-primary text-white" style="font-size:0.7rem">{{ nextLevelName }}</span>
          <input type="text" class="form-control" v-model="filter"
                 placeholder="Ketik untuk filter..."
                 style="font-size:0.8rem">
          <span class="input-group-text" style="font-size:0.7rem">
            <i class="bi bi-list-ul me-1"></i>{{ filteredChildList.length }}
          </span>
        </div>
      </div>

      <!-- Child list: fills remaining space, scrolls only if needed -->
      <div v-if="showList && pathData.length > 0 && pathData[pathData.length-1].level < maxLevel"
           class="list-group list-group-flush flex-grow-1 overflow-auto" style="min-height:0">
        <div v-if="filteredChildList.length === 0" class="list-group-item text-muted small text-center py-3">
          <i class="bi bi-search me-1"></i>Tidak ditemukan
        </div>
        <a v-for="item in filteredChildList" :key="item.id" href="#"
           class="list-group-item list-group-item-action d-flex align-items-center py-2"
           @click.prevent="pickChild(item.id)">
          <i class="bi bi-geo text-muted me-2" style="font-size:0.75rem"></i>
          <span class="small">{{ item.nama }}</span>
          <span v-if="item.children > 0" class="badge bg-body-secondary text-muted ms-auto" style="font-size:0.65rem">{{ item.children }}</span>
        </a>
      </div>

      <!-- Spacer when list hidden: push button to bottom -->
      <div v-else class="flex-grow-1"></div>

      <!-- Select button -->
      <div v-if="pathData.length > 0 && lastPath" class="px-3 py-3 flex-shrink-0 border-top">
        <button class="btn btn-sm btn-primary w-100 rounded-3" @click="selectFromPath(lastPath)"
                :disabled="config.wilayah_id == lastPath.id">
          <i class="bi bi-check-circle me-1"></i>
          Pilih: {{ lastPath.caption || lastPath.nama }}
        </button>
      </div>

    </template>
  </div>
</template>

<script>
module.exports = {
  name: 'wilayah',
  data() {
    return {
      config: { userRole: '', locked: false, wilayah_nama: '', wilayah_id: null, wilayah_level: '', wilayah_parent_id: null },
      pathData: [],
      childList: [],
      selectedId: '',
      filter: '',
      showList: true,
      hasMultipleRoots: false,
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
      this.showList = false;
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
          this.updateTopbar();
        })
        .catch(e => { this.loading = false; this.handleError(e); });
    },
    loadChildren() {
      if (this.pathData.length === 0) return;
      var last = this.pathData[this.pathData.length - 1];
      if (last.level >= this.maxLevel) {
        this.childList = [];
        return;
      }
      var parentId = last.level == 0 ? -1 : last.id;
      axios.get('/gov2wilayah/sidepanel/listWilayah/1/' + parentId)
        .then(resp => {
          var data = resp.data;
          this.childList = Array.isArray(data) ? data : [];
          if (this.pathData.length === 1) {
            if (this.childList.length === 1) {
              this.hasMultipleRoots = false;
              this.pickChild(this.childList[0].id);
              return;
            }
            this.hasMultipleRoots = this.childList.length > 1;
          }
          this.showList = true;
        })
        .catch(e => this.handleError(e));
    },
    pickChild(id) {
      this.selectedId = id;
      this.showList = false;
      this.filter = '';
      this.drillDown();
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
        this.loadBreadcrumb(id);
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
            var isBold = (p.id == this.config.wilayah_id) ? ' fw-bold text-primary' : '';
            html += '<div class="px-3 py-1">' +
              '<div class="text-muted" style="font-size:0.65rem">' + this.escapeHtml(lbl) + '</div>' +
              '<div class="' + isBold + '" style="font-size:0.8rem">' + this.escapeHtml(p.caption || p.nama) + '</div>' +
              '</div>';
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
.icon-box.sm {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
</style>
