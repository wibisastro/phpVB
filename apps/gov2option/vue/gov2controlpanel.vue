<template>
  <div class="accordion" role="tablist">
    <b-card no-body class="mb-1" v-for="kementerian in units" v-bind:key="kementerian.id">
      <gov2controlpanel-unit :unit="kementerian" :get-url="getUrl" :app="app">
      </gov2controlpanel-unit>
    </b-card>
  </div>
</template>

<script>

module.exports = {
  name: 'gov2controlpanel',
  props:  {
    getUrl: String,
    app: String
  },
  components: {
    'gov2controlpanel-unit': httpVueLoader('./gov2controlpanel-unit.vue')
  },
  data: function () {
    return {
      units: [],
    }
  },
  methods: {
    getUnits: function () {
      axios.get(`${this.getUrl}/units`)
          .then(resp => {
            this.units = resp.data;
          })
          .catch(e => console.log('Error while fetching items data'));
    },
    refresh() {
      this.getUnits();
    }
  },
  created: function () {
    this.getUnits();
    eventBus.$on('gov2controlpanel-refresh', this.refresh)
  }
}
</script>
<style>
.pointer {
  cursor: pointer;
}
</style>