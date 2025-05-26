<template>
  <div>
    <b-card-header class="pointer" :class="{'pl-5': parseInt(unit.level) == 3}" role="tab" @click="toggleCollapse(unit)">
      {{unit.kode}} - {{ unit.nama }}
      <b-spinner variant="info" small v-if="loading"></b-spinner>
      <span class="subtitle" v-if="unit.keterangan">[{{ unit.keterangan }}]</span>
    </b-card-header>

    <b-collapse :id="`collapse-${unit.id}`" role="tabpanel" accordion="unit-accordion">
      <div class="card-body">

        <div class="accordion" role="tablist">
          <b-card no-body class="mb-1" v-for="mvc in child" v-bind:key="mvc.id">
            <gov2controlpanel-item :cluster="mvc" :get-url="getUrl" :app="app" :portal="unit.portal">
            </gov2controlpanel-item>
          </b-card>
        </div>
      </div>
    </b-collapse>
  </div>
</template>

<script>

module.exports = {
  name: 'gov2controlpanel-unit',
  props:  {
    unit: Object,
    getUrl: String,
    app: String,
    type: String
  },
  components: {
    'gov2controlpanel-item': httpVueLoader('./gov2controlpanel-item.vue')
  },
  data: function () {
    return {
      status: [],
      collapsed: {},
      child: [],
      loading: false,
      selected: '',
      values: []
    }
  },
  methods: {
    toggleCollapse: function (unit) {
      if(this.child.length < 1) {
        this.getApps(unit);
      }
      this.collapsed[unit.id] = !this.collapsed[unit.id];
      this.$root.$emit('bv::toggle::collapse', `collapse-${unit.id}`)
    },
    getApps: function (cluster) {
      this.loading = true;
      const url = `${this.getUrl}/options/${cluster.portal}`;
      axios.get(url)
          .then(resp => {
            this.child = Array.from(Object.keys(resp.data), k=>resp.data[k]);
            resp.data.forEach(row => {
              if (row && row.type === 'radio') {
                if (row.value !== "" && row.value !== null) {
                  this.selected = row.id;
                }
              }
            });
            this.loading = false;
          })
          .catch(e => {
            console.log(e);
            this.loading = false;
          })
    },
  },
  mounted() {

  },
  filters: {
    capitalize: function (value) {
      if (!value) return ''
      value = value.toString()
      return value.charAt(0).toUpperCase() + value.slice(1)
    },
    uppercase: function (value) {
      if (!value) return ''
      value = value.toString()
      return value.toUpperCase()
    }
  }
}
</script>
<style scoped>
.pointer {
  cursor: pointer;
}
.subtitle {
  font-size: 14px;
  color: darkgray;
  float: right;
}
</style>