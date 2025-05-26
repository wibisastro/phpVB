<template>
  <b-form-timepicker
      v-model="id" >
  </b-form-timepicker>
</template>

<script>
    module.exports = {
        name: "field-timepicker",
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
            eventBus.$emit(`set_chain`, this.id);
          }
        }
    }
</script>

<style scoped>
  
</style>
