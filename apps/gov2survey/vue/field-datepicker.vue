<template>
  <input type="date" class="form-control" v-model="id">
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
