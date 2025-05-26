<template>
    <b-form-input v-model="id"></b-form-input>
</template>

<script>
    module.exports = {
        name: "field-judul-kuesioner",
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
          setName(payload) {
            if (payload.hasOwnProperty('text')) {
              this.id = payload.text.trim();
            } else {
              console.log('field-judul-kuesioner : Payload doens\'t have `text` property');
            }
          }
        },
        created: function () {
          eventBus.$on('field-referensi-kuesioner', this.setName)
        },
        watch: {
          value: function(){
              this.id = this.value;
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
