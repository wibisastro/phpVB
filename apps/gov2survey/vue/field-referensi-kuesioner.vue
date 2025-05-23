<template>
    <b-form-select v-model="id" :options="options"> 
      <template #first>
        <b-form-select-option :value="undefined" disabled>-- Pilih Dropdown --</b-form-select-option>
      </template>
    </b-form-select>
</template>

<script>
    module.exports = {
        name: "field-referensi-kuesioner",
        props: {
            value:0,
            getUrl: {
              type: String,
              default: "gov2survey"
            }
        }, 
        data: function () {
          return {
            id: this.value,
            options: [],
            proses: false
          }
        },
        methods: {
          initialize: function () {
            const paths = window.location.pathname.split('/');
            axios.get(`/${this.getUrl}/${paths[2]}/kuesioner/referensi`)
              .then(response => {
                  if (response.data) {
                    this.options = Array.from(response.data);
                  }
              })
              .catch(err => {
                console.log(err)
              })
          },
        },
        created: function () {
          this.initialize();
        },
        watch: {
          value: function(){
              this.id = this.value;
          },
          id(nv) {
            this.$emit('input', nv);
            this.options.forEach(opt => {
              if (nv == opt.value) {
                eventBus.$emit('field-referensi-kuesioner', opt);
              }
            })
            eventBus.$emit(`set_chain`, this.id);
          }
        }
    }
</script>

<style scoped>
  
</style>
