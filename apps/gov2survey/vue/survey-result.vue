<template>
    <b-overlay :show="loading">
       <div class="row clearfix">
          <div class="col-lg-12">
            <highchart-bar height="100%" v-bind:key="`instansi-jumlah`"
               :title="{text: title}"
               :chart="{type: 'bar'}"
               :x-axis="{categories: categories}" 
               :legend="{verticalAlign:'top'}"
               :tooltip="tooltip"
               :plot-options="{series: {stacking: 'percentage'}}"
               :y-axis="barYAxis" 
               :series="series" :loading="loading">
            </highchart-bar>
          </div>
       </div>
    </b-overlay>
</template>
<script>
module.exports = {
   name: "survey-result",
   props: {
      getApp: String,
      getCmd: String
   },
   methods: {
      initialize() {
         this.loading = true;
         const url = `${this.getApp}/${this.getCmd}/-1`;

         axios.get(url)
         .then(res => {
            if (res.hasOwnProperty('data')) {
               if (res.data.hasOwnProperty('class')) {
                  eventBus.$emit('openNotif', res.data);
               } else {
                  this.generateData(res.data);
               }
            } else {
               this.loading = false;
               console.log(res);
            }
         })
         .catch(e => {
            this.loading = false;
            console.log(e);
         })
      },
      generateData(data) {
         const categories = data && data.categories.length ? Array.from(data.categories) : [];
         const series = data && data.series.length ? Array.from(data.series) : [];
         
         if (series.length) {
            series.forEach((serie, i) => {
                  categories.forEach((cat, index) => {
                     if (!serie.data[index]) {
                        series[i].data[index] = 0;
                     }
                  })
            })
         }

         this.$set(this, 'title', data.survey);
         this.$set(this, 'categories', categories);
         this.series = series;
         this.loading = false;
      }
   },
   created() {
      this.initialize();
   },
   mounted() {

   },
   data() {
      return {
         loading: false,
         title: "",
         series: [],
         categories: [],
         barYAxis: {
            allowDecimals: false,
            min: 0,
            title: {
               text: 'Responden'
            }
         },
         tooltip: {
            pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>',
            shared: true}
         }
   },
    components: {
        'highchart-bar': httpVueLoader('./highchart-bar.vue'),
    }
}
</script>
<style scoped>

</style>