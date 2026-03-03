<template>
  <p class="text-uppercase fw-bold small text-muted px-3 mb-1">
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

<style></style>