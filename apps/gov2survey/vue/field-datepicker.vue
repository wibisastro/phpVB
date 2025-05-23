<template>
  <b-form-datepicker
      v-model="id" 
      :min="new Date()"
      :max="''" 
      locale="id">
  </b-form-datepicker>
</template>

<script>
    module.exports = {
        name: "field-datepicker",
        props: {
            value:0
        }, 
        data: function () {
          return {
            id: this.value,
            proses: false
          }
        },
        methods: {
        },
        created: function () {
        },
        watch: {
          value(nv) {
              this.id = nv;
          },
          id(nv) {
            this.$emit('input', nv);
            eventBus.$emit(`set_chain`, nv);
          }
        }
    }
</script>

<style scoped>
  
</style>
