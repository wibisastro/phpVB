<template>
  <p class="menu-label">
      <slot>{{ menuLabel }}</slot>
  </p>
</template>

<script>

module.exports = {
    name: 'menu-label',
    data: function () {
        return {
            menuLabel: 'default'
        }
    }
}
</script>

<style>

</style>