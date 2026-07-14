<template>
  <div>
    <!-- Header: label + discovery configurable via options global home (#6134) -->
    <div class="px-3 py-2 border-bottom bg-body-tertiary">
      <div class="small text-muted">
        <i class="bi bi-diagram-3 me-1"></i>
        Konektor ekosistem gov3 — registry {{ label }}
      </div>
      <div v-if="discoveryUrl" class="small text-truncate">
        <i class="bi bi-broadcast-pin me-1 text-muted"></i>
        <span class="text-muted">Discovery:</span> {{ discoveryUrl }}
      </div>
      <input type="text" class="form-control form-control-sm mt-2" disabled
             placeholder="Cari gurita by kata kunci — menunggu discovery channel">
    </div>

    <div v-if="forbidden" class="px-4 py-3 text-muted small">
      <i class="bi bi-shield-lock me-1"></i>Panel {{ label }} khusus webmaster.
    </div>

    <div v-else>
      <!-- Register by URL -->
      <div class="px-3 py-2 border-bottom">
        <button class="btn btn-sm w-100" :class="showForm ? 'btn-outline-secondary' : 'btn-primary'"
                @click="toggleForm">
          <i class="bi me-1" :class="showForm ? 'bi-x-lg' : 'bi-plus-lg'"></i>
          {{ showForm ? 'Batal' : (form.id ? 'Ubah koneksi' : 'Daftarkan gurita by URL') }}
        </button>

        <form v-if="showForm" class="mt-2" @submit.prevent="saveConn">
          <input v-model.trim="form.nama" type="text" class="form-control form-control-sm mb-2"
                 placeholder="Nama, mis. Gurita Kemdikbud" required maxlength="190">
          <input v-model.trim="form.url" type="url" class="form-control form-control-sm mb-2"
                 placeholder="https://gurita.instansi.go.id/mcp/..." required maxlength="255">
          <select v-model="form.auth_type" class="form-select form-select-sm mb-2">
            <option value="none">tanpa auth</option>
            <option value="bearer">bearer</option>
            <option value="basic">basic</option>
            <option value="apikey">apikey</option>
          </select>
          <input v-if="form.auth_type !== 'none'" v-model="form.credential" type="password"
                 class="form-control form-control-sm mb-2" autocomplete="new-password"
                 :placeholder="form.id ? 'Credential (kosongkan bila tetap)' : 'Credential'">
          <button class="btn btn-sm btn-primary w-100" type="submit" :disabled="busy">
            <span v-if="busy" class="spinner-border spinner-border-sm me-1"></span>
            Simpan &amp; discover tools
          </button>
        </form>
      </div>

      <!-- Daftar koneksi -->
      <div v-if="loading" class="px-4 py-3 text-muted small">
        <span class="spinner-border spinner-border-sm me-2"></span>Memuat…
      </div>
      <div v-else-if="guritaConnections.length === 0" class="px-4 py-3 text-muted small">
        <i class="bi bi-info-circle me-1"></i>Belum ada gurita terdaftar
      </div>

      <div v-for="c in guritaConnections" :key="c.id" class="border-bottom px-3 py-2">
        <div class="d-flex align-items-center">
          <div class="flex-grow-1 text-truncate">
            <span class="badge me-1" :class="c.status === 'on' ? 'bg-primary' : 'bg-secondary'">{{ c.jenis }}</span>
            <span class="small fw-semibold">{{ c.nama }}</span>
            <div class="small text-muted text-truncate">{{ c.url }}</div>
          </div>
          <div class="btn-group btn-group-sm ms-1">
            <button class="btn btn-outline-secondary" title="Browse tools" @click="toggleTools(c)">
              <i class="bi" :class="expandedId === c.id ? 'bi-chevron-up' : 'bi-tools'"></i>
            </button>
            <button class="btn btn-outline-secondary" title="Refresh inventori tools" @click="refreshTools(c)">
              <i class="bi bi-arrow-repeat"></i>
            </button>
            <button class="btn btn-outline-secondary" title="Ubah" @click="editConn(c)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-outline-danger" title="Hapus" @click="delConn(c)">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>

        <!-- Tools + import -->
        <div v-if="expandedId === c.id" class="mt-2 ps-2 border-start">
          <div v-if="!inventories[c.id]" class="small text-muted">
            <span class="spinner-border spinner-border-sm me-1"></span>Memuat tools…
          </div>
          <template v-else>
            <div v-if="inventories[c.id].length === 0" class="small text-muted">
              Belum ada inventori tools — klik <i class="bi bi-arrow-repeat"></i> untuk discover.
            </div>
            <template v-else>
              <select v-model="importForm.tool" class="form-select form-select-sm mb-2">
                <option value="" disabled>Pilih tool…</option>
                <option v-for="t in inventories[c.id]" :key="t.name" :value="t.name">{{ t.name }}</option>
              </select>
              <div v-if="toolDescription(c.id)" class="small text-muted mb-2">{{ toolDescription(c.id) }}</div>
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text">app tujuan</span>
                <input v-model.trim="importForm.app" type="text" class="form-control"
                       :placeholder="pageID || 'home'">
                <button class="btn btn-primary" :disabled="!importForm.tool || busy" @click="importTool(c)">
                  <i class="bi bi-download me-1"></i>Import
                </button>
              </div>
              <div class="small text-muted mb-1">
                Import menyalin daftar dari gurita ke options app tujuan (save as local).
              </div>
            </template>
          </template>
        </div>
      </div>

      <!-- Save-to-lower-tier -->
      <div class="px-3 py-2">
        <div class="small fw-semibold mb-1"><i class="bi bi-pin-angle me-1"></i>Save-to-lower-tier (pin)</div>
        <div class="input-group input-group-sm">
          <span class="input-group-text">app</span>
          <input v-model.trim="pinApp" type="text" class="form-control" :placeholder="pageID || 'home'">
          <button class="btn btn-outline-primary" :disabled="busy" @click="pin">
            <i class="bi bi-pin me-1"></i>Pin
          </button>
        </div>
        <div class="small text-muted mt-1">
          Materialisasi options satu app dari DB ke pinned JSON (kambing + cache lokal).
        </div>
      </div>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'cube-gurita',
  data() {
    return {
      loading: true,
      busy: false,
      forbidden: false,
      connections: [],
      jenisList: ['gurita'],
      label: 'Gurita',
      discoveryUrl: '',
      showForm: false,
      form: this.emptyForm(),
      expandedId: null,
      inventories: {},
      importForm: { tool: '', app: '' },
      pinApp: '',
      pageID: ''
    }
  },
  computed: {
    // Accordion ini khusus fauna gurita — kambing/gajah punya accordion sendiri
    guritaConnections() {
      return this.connections.filter(c => c.jenis === 'gurita');
    }
  },
  methods: {
    emptyForm() {
      return { id: 0, jenis: 'gurita', nama: '', url: '', auth_type: 'none', credential: '', status: 'on' };
    },
    notify(data, fallback) {
      window.eventBus.$emit('openNotif', {
        class: (data && data.class) || 'is-info',
        notification: (data && data.notification) || fallback || ''
      });
    },
    failNotify(e, fallback) {
      var data = e && e.response && e.response.data;
      this.notify({ class: 'is-danger', notification: (data && data.notification) || fallback });
    },
    loadData() {
      this.pageID = window.location.pathname.split('/')[1] || '';
      axios.get('/gov2option/connection/webmaster/getList')
        .then(resp => {
          this.connections = resp.data.connections || [];
          this.jenisList = resp.data.jenis || ['gurita'];
          this.label = resp.data.label || 'Gurita';
          this.discoveryUrl = resp.data.discovery_url || '';
          this.forbidden = false;
        })
        .catch(e => {
          var status = e && e.response && e.response.status;
          this.forbidden = (status === 401 || status === 403);
        })
        .finally(() => { this.loading = false; });
    },
    toggleForm() {
      this.showForm = !this.showForm;
      if (!this.showForm) { this.form = this.emptyForm(); }
    },
    saveConn() {
      this.busy = true;
      axios.post('/gov2option/connection/webmaster', { cmd: 'save', data: this.form })
        .then(resp => {
          this.notify(resp.data, 'Koneksi tersimpan');
          this.form = this.emptyForm();
          this.showForm = false;
          this.inventories = {};
          this.loadData();
        })
        .catch(e => this.failNotify(e, 'Simpan koneksi gagal'))
        .finally(() => { this.busy = false; });
    },
    editConn(c) {
      this.form = { id: c.id, jenis: c.jenis, nama: c.nama, url: c.url,
                    auth_type: c.auth_type, credential: '', status: c.status };
      this.showForm = true;
    },
    delConn(c) {
      if (!confirm('Hapus koneksi "' + c.nama + '"?')) { return; }
      axios.post('/gov2option/connection/webmaster', { cmd: 'del', data: { id: c.id } })
        .then(resp => { this.notify(resp.data, 'Koneksi dihapus'); this.loadData(); })
        .catch(e => this.failNotify(e, 'Hapus koneksi gagal'));
    },
    refreshTools(c) {
      this.busy = true;
      axios.post('/gov2option/connection/webmaster', { cmd: 'tools', data: { id: c.id } })
        .then(resp => {
          this.notify(resp.data, 'Inventori tools diperbarui');
          delete this.inventories[c.id];
          if (this.expandedId === c.id) { this.fetchInventory(c.id); }
          this.loadData();
        })
        .catch(e => this.failNotify(e, 'Discover tools gagal'))
        .finally(() => { this.busy = false; });
    },
    toggleTools(c) {
      if (this.expandedId === c.id) { this.expandedId = null; return; }
      this.expandedId = c.id;
      this.importForm = { tool: '', app: '' };
      if (!this.inventories[c.id]) { this.fetchInventory(c.id); }
    },
    fetchInventory(id) {
      axios.get('/gov2option/connection/webmaster/inventory/' + id)
        .then(resp => { this.inventories = Object.assign({}, this.inventories, { [id]: resp.data.tools || [] }); })
        .catch(() => { this.inventories = Object.assign({}, this.inventories, { [id]: [] }); });
    },
    toolDescription(id) {
      var tool = (this.inventories[id] || []).find(t => t.name === this.importForm.tool);
      return tool ? tool.description : '';
    },
    importTool(c) {
      this.busy = true;
      axios.post('/gov2option/connection/webmaster', {
        cmd: 'import',
        data: { id: c.id, tool: this.importForm.tool, app: this.importForm.app || this.pageID || 'home' }
      })
        .then(resp => this.notify(resp.data, 'Import selesai'))
        .catch(e => this.failNotify(e, 'Import gagal'))
        .finally(() => { this.busy = false; });
    },
    pin() {
      this.busy = true;
      axios.post('/gov2option/connection/webmaster', {
        cmd: 'pin',
        data: { app: this.pinApp || this.pageID || 'home' }
      })
        .then(resp => this.notify(resp.data, 'Pinned'))
        .catch(e => this.failNotify(e, 'Pin gagal'))
        .finally(() => { this.busy = false; });
    }
  },
  created() {
    this.loadData();
  },
  mounted() {
    // Kini embed di accordion panel Pengaturan (mount hanya utk webmaster)
    var el = document.getElementById('sidePanelOffcanvas');
    if (el) {
      el.addEventListener('show.bs.offcanvas', () => this.loadData());
    }
  }
}
</script>
