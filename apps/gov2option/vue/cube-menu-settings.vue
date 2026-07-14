<template>
  <div>
    <div class="accordion accordion-flush" id="settingsAccordion">

      <!-- Options -->
      <div class="accordion-item border-0">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold small py-3 px-3" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseOptions"
                  aria-expanded="false" aria-controls="collapseOptions">
            <i class="bi bi-sliders me-2 text-primary"></i>Options
          </button>
        </h2>
        <div id="collapseOptions" class="accordion-collapse collapse">
          <div class="accordion-body p-0">
            <ul class="list-group list-group-flush" v-if="options.length > 0">
              <li class="list-group-item border-0 px-4 py-2 d-flex align-items-center" v-for="item in options" :key="item.app">
                <a :href="`/${item.app}/options/view`"
                   class="d-flex align-items-center text-decoration-none text-body flex-grow-1">
                  <i class="bi bi-gear me-2 text-muted"></i>
                  <span class="small">{{ item.app.toUpperCase() }}</span>
                </a>
                <!-- Pengganti obscurity /setup (#6134): webmaster dapat link
                     eksplisit; route lama tetap hidup & tetap gated -->
                <a v-if="userRole === 'webmaster'" :href="`/${item.app}/options/setup`"
                   class="text-decoration-none text-muted ms-2" title="Setup definisi options">
                  <i class="bi bi-wrench-adjustable small"></i>
                </a>
              </li>
            </ul>
            <div v-else class="px-4 py-2 text-muted small">
              <i class="bi bi-info-circle me-1"></i>Belum ada options
              <a v-if="userRole === 'webmaster' && pageID" :href="`/${pageID}/options/setup`"
                 class="d-block mt-1 text-decoration-none">
                <i class="bi bi-wrench-adjustable me-1"></i>Setup options {{ pageID.toUpperCase() }}
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Services -->
      <div class="accordion-item border-0">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold small py-3 px-3" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseServices"
                  aria-expanded="false" aria-controls="collapseServices">
            <i class="bi bi-diagram-3 me-2 text-primary"></i>Services
          </button>
        </h2>
        <div id="collapseServices" class="accordion-collapse collapse">
          <div class="accordion-body p-0">
            <ul class="list-group list-group-flush" v-if="services.length > 0">
              <li class="list-group-item border-0 px-4 py-2 d-flex align-items-center" v-for="item in services" :key="item.app">
                <a :href="`/${item.app}/services/view`"
                   class="d-flex align-items-center text-decoration-none text-body flex-grow-1">
                  <i class="bi bi-plug me-2 text-muted"></i>
                  <span class="small">{{ item.app.toUpperCase() }}</span>
                </a>
                <a v-if="userRole === 'webmaster'" :href="`/${item.app}/services/setup`"
                   class="text-decoration-none text-muted ms-2" title="Setup definisi services">
                  <i class="bi bi-wrench-adjustable small"></i>
                </a>
              </li>
            </ul>
            <div v-else class="px-4 py-2 text-muted small">
              <i class="bi bi-info-circle me-1"></i>Belum ada services
            </div>
          </div>
        </div>
      </div>

      <!-- Tahun -->
      <div class="accordion-item border-0" v-if="optionTahun.length > 0">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold small py-3 px-3" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseTahun"
                  aria-expanded="false" aria-controls="collapseTahun">
            <i class="bi bi-calendar3 me-2 text-primary"></i>
            Tahun
            <span v-if="activeYear" class="badge bg-primary ms-2 fw-normal">{{ activeYear }}</span>
          </button>
        </h2>
        <div id="collapseTahun" class="accordion-collapse collapse">
          <div class="accordion-body p-0">
            <ul class="list-group list-group-flush">
              <li class="list-group-item border-0 px-4 py-2" v-for="item in optionTahun" :key="item.nama">
                <a href="#" @click.prevent="setYear(item.nama)"
                   class="d-flex align-items-center text-decoration-none"
                   :class="item.nama == activeYear ? 'text-primary fw-semibold' : 'text-body'">
                  <i class="bi me-2" :class="item.nama == activeYear ? 'bi-check-circle-fill text-primary' : 'bi-circle text-muted'"></i>
                  <span class="small">{{ item.nama }}</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Role -->
      <div class="accordion-item border-0" v-if="userRole">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed fw-semibold small py-3 px-3" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseRole"
                  aria-expanded="false" aria-controls="collapseRole">
            <i class="bi bi-person-badge me-2 text-primary"></i>
            Role
            <span class="badge bg-secondary ms-2 fw-normal">{{ userRole }}</span>
          </button>
        </h2>
        <div id="collapseRole" class="accordion-collapse collapse">
          <div class="accordion-body p-0">
            <ul class="list-group list-group-flush" v-if="myRoles.length > 0">
              <li class="list-group-item border-0 px-4 py-2" v-for="role in myRoles" :key="role">
                <a :href="`/${pageID}/role/${role}`"
                   class="d-flex align-items-center text-decoration-none"
                   :class="role == userRole ? 'text-primary fw-semibold' : 'text-body'">
                  <i class="bi me-2" :class="role == userRole ? 'bi-check-circle-fill text-primary' : 'bi-person-circle text-muted'"></i>
                  <span class="small">{{ role }}</span>
                </a>
              </li>
            </ul>
            <div v-else class="px-4 py-2 text-muted small">
              <i class="bi bi-info-circle me-1"></i>Belum ada roles
            </div>
          </div>
        </div>
      </div>

      <!-- Konektor gov3 (#6134): tiap fauna accordion sendiri — onboarding
           path & form field beda-beda (gurita: domain MCP; kambing: WebDAV +
           app password; gajah: endpoint realtime/edge + apikey) -->
      <template v-if="userRole === 'webmaster'">
        <div class="accordion-item border-0">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed fw-semibold small py-3 px-3" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseGurita"
                    aria-expanded="false" aria-controls="collapseGurita">
              <i class="bi bi-bezier2 me-2 text-primary"></i>Gurita
            </button>
          </h2>
          <div id="collapseGurita" class="accordion-collapse collapse">
            <div class="accordion-body p-0">
              <cube-gurita></cube-gurita>
            </div>
          </div>
        </div>

        <div class="accordion-item border-0">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed fw-semibold small py-3 px-3" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseKambing"
                    aria-expanded="false" aria-controls="collapseKambing">
              <i class="bi bi-hdd-stack me-2 text-primary"></i>Kambing
            </button>
          </h2>
          <div id="collapseKambing" class="accordion-collapse collapse">
            <div class="accordion-body p-0">
              <cube-kambing></cube-kambing>
            </div>
          </div>
        </div>

        <div class="accordion-item border-0">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed fw-semibold small py-3 px-3" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseGajah"
                    aria-expanded="false" aria-controls="collapseGajah">
              <i class="bi bi-database me-2 text-primary"></i>Gajah
            </button>
          </h2>
          <div id="collapseGajah" class="accordion-collapse collapse">
            <div class="accordion-body p-0">
              <cube-gajah></cube-gajah>
            </div>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'cube-menu-settings',
  data() {
    return {
      options: [],
      services: [],
      userRole: '',
      optionTahun: [],
      activeYear: null,
      myRoles: [],
      pageID: ''
    }
  },
  methods: {
    loadData() {
      this.pageID = window.location.pathname.split('/')[1] || '';

      axios.get('/gov2option/index/getList')
        .then(resp => {
          this.options = resp.data.options || [];
          this.services = resp.data.services || [];
          this.userRole = resp.data.userRole || '';
        })
        .catch(e => console.log('cube-menu-settings getList:', e.message));

      if (this.pageID) {
        axios.get('/gov2option/index/getYearOptions/' + this.pageID)
          .then(resp => {
            this.optionTahun = resp.data.optionTahun || [];
            this.activeYear = resp.data.activeYear || null;
          })
          .catch(e => console.log('cube-menu-settings getYearOptions:', e.message));

        axios.get('/gov2option/index/getPageroles/' + this.pageID)
          .then(resp => {
            if (resp.data && typeof resp.data === 'object' && !Array.isArray(resp.data)) {
              this.myRoles = Object.keys(resp.data);
            }
          })
          .catch(() => { this.myRoles = []; });
      }
    },
    setYear(year) {
      axios.post('/gov2option/index/setYear', { pageID: this.pageID, year: year })
        .then(() => {
          this.activeYear = year;
          location.reload();
        })
        .catch(e => console.log('cube-menu-settings setYear:', e.message));
    }
  },
  created() {
    this.loadData();
  },
  mounted() {
    var el = document.getElementById('sidePanelOffcanvas');
    if (el) {
      el.addEventListener('show.bs.offcanvas', () => this.loadData());
    }
  }
}
</script>
