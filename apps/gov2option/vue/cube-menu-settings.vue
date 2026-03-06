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
              <li class="list-group-item border-0 px-4 py-2" v-for="item in options" :key="item.app">
                <a :href="`/${item.app}/options/view`"
                   class="d-flex align-items-center text-decoration-none text-body">
                  <i class="bi bi-gear me-2 text-muted"></i>
                  <span class="small">{{ item.app.toUpperCase() }}</span>
                </a>
              </li>
            </ul>
            <div v-else class="px-4 py-2 text-muted small">
              <i class="bi bi-info-circle me-1"></i>Belum ada options
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
              <li class="list-group-item border-0 px-4 py-2" v-for="item in services" :key="item.app">
                <a :href="`/${item.app}/services/view_services`"
                   class="d-flex align-items-center text-decoration-none text-body">
                  <i class="bi bi-plug me-2 text-muted"></i>
                  <span class="small">{{ item.app.toUpperCase() }}</span>
                </a>
              </li>
            </ul>
            <div v-else class="px-4 py-2 text-muted small">
              <i class="bi bi-info-circle me-1"></i>Belum ada services
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'cube-menu-settings',
  data() {
    return {
      options: [],
      services: []
    }
  },
  methods: {
    loadData() {
      axios.get('/gov2option/index/getList')
        .then(resp => {
          this.options = resp.data.options || [];
          this.services = resp.data.services || [];
        })
        .catch(e => console.log('cube-menu-settings:', e.message));
    }
  },
  created() {
    this.loadData();
  },
  mounted() {
    var el = document.getElementById('optionsOffcanvas');
    if (el) {
      el.addEventListener('show.bs.offcanvas', () => this.loadData());
    }
  }
}
</script>
