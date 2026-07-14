<template>
  <div>
    <div class="px-3 py-2 border-bottom bg-body-tertiary small text-muted">
      <i class="bi bi-database me-1"></i>
      Layanan gajah (supabase realtime/edge) — onboarding: endpoint layanan + apikey.
      Bisa banyak endpoint per portal; penerapan default notification dropdown menyusul.
    </div>

    <div class="px-3 py-2 border-bottom">
      <form @submit.prevent="save">
        <input v-model.trim="form.nama" type="text" class="form-control form-control-sm mb-2"
               placeholder="Nama layanan, mis. Notifikasi Agent Run" required maxlength="190">
        <input v-model.trim="form.url" type="url" class="form-control form-control-sm mb-2"
               placeholder="https://gajah.gov3.id/realtime/v1/... atau /functions/v1/..." required maxlength="255">
        <input v-model="form.apikey" type="password" class="form-control form-control-sm mb-2"
               placeholder="apikey (anon key)" required autocomplete="new-password">
        <button class="btn btn-sm btn-primary w-100" type="submit" :disabled="busy">
          <span v-if="busy" class="spinner-border spinner-border-sm me-1"></span>
          Daftarkan endpoint gajah
        </button>
      </form>
    </div>

    <div v-if="connections.length === 0" class="px-4 py-2 text-muted small">
      <i class="bi bi-info-circle me-1"></i>Belum ada endpoint gajah terdaftar
    </div>
    <div v-for="c in connections" :key="c.id" class="border-bottom px-3 py-2 d-flex align-items-center">
      <div class="flex-grow-1 text-truncate">
        <span class="small fw-semibold">{{ c.nama }}</span>
        <span v-if="c.has_credential" class="badge bg-secondary ms-1" title="apikey tersimpan terenkripsi">
          <i class="bi bi-key"></i>
        </span>
        <div class="small text-muted text-truncate">{{ c.url }}</div>
      </div>
      <button class="btn btn-sm btn-outline-danger ms-1" title="Hapus" @click="del(c)">
        <i class="bi bi-trash"></i>
      </button>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'cube-gajah',
  data() {
    return {
      busy: false,
      connections: [],
      form: { nama: '', url: '', apikey: '' }
    }
  },
  methods: {
    notify(data, fallback) {
      window.eventBus.$emit('openNotif', {
        class: (data && data.class) || 'is-info',
        notification: (data && data.notification) || fallback || ''
      });
    },
    loadData() {
      axios.get('/gov2option/connection/webmaster/getList')
        .then(resp => {
          this.connections = (resp.data.connections || []).filter(c => c.jenis === 'gajah');
        })
        .catch(() => { this.connections = []; });
    },
    save() {
      this.busy = true;
      axios.post('/gov2option/connection/webmaster', {
        cmd: 'save',
        data: {
          jenis: 'gajah',
          nama: this.form.nama,
          url: this.form.url,
          auth_type: 'apikey',
          credential: this.form.apikey
        }
      })
        .then(resp => {
          this.notify(resp.data, 'Endpoint gajah terdaftar');
          this.form = { nama: '', url: '', apikey: '' };
          this.loadData();
        })
        .catch(e => this.notify({ class: 'is-danger',
          notification: (e.response && e.response.data && e.response.data.notification) || 'Simpan gagal' }))
        .finally(() => { this.busy = false; });
    },
    del(c) {
      if (!confirm('Hapus endpoint "' + c.nama + '"?')) { return; }
      axios.post('/gov2option/connection/webmaster', { cmd: 'del', data: { id: c.id } })
        .then(resp => { this.notify(resp.data, 'Terhapus'); this.loadData(); })
        .catch(() => this.notify({ class: 'is-danger', notification: 'Hapus gagal' }));
    }
  },
  created() {
    this.loadData();
  }
}
</script>
