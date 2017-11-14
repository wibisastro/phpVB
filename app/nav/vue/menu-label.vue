<template>
  <p class="menu-label">
      <slot>{{ menuLabel }}</slot>
  </p>
</template>

<script>
    
//import menu-list from './_menu-list.vue';

module.exports = {
    name: 'menu-label',
//    components: { menu-list },
    data: function() {
        return {
            menuLabel: 'default'
        }
    }
}
</script>

<style>

</style>