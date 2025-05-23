<template>
  <div class="accordion" role="tablist">
    <b-card no-body class="mb-1" v-for="cluster in clusters" v-bind:key="cluster.id">
      <gov2option-item :cluster="cluster"
                       :get-url="getUrl"
                       :app="app"
                       :type="type">
      </gov2option-item>
    </b-card>
  </div>
</template>

<script>

module.exports = {
  name: 'gov2option',
  props:  {
    getUrl: String,
    app: String,
    type: String
  },
  components: {
    'gov2option-item': httpVueLoader('./gov2option-item.vue')
  },
  data: function () {
    return {
      clusters: [],
    }
  },
  methods: {
    getData: function () {
      const url = `/gov2option/${this.app}/option/${this.type}`;
      axios.get(url)
          .then(resp => {
            this.clusters = resp.data;
          })
          .catch(e => console.log('Error while fetching items data'));
    },
    refresh() {
      this.getData();
    }
  },
  created: function () {
    this.getData();
    eventBus.$on('gov2option-refresh', this.refresh)
  }
}
</script>
<style>
.pointer {
  cursor: pointer;
}
</style>