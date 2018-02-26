<template>
<div>
    <nav class="breadcrumb has-arrow-separator" aria-label="breadcrumbs">
      <ul>
        <li><a href="#">Bulma</a></li>
        <li><a href="#">Documentation</a></li>
        <li><a href="#">Components</a></li>
        <li class="is-active"><a href="#" aria-current="page">Breadcrumb</a></li>
      </ul>
    </nav>
</div>
</template>

<script>
module.exports = {
    name: 'breadcrumb',
    data () {
        return {
            breadcrumb: 'Bulma',
        }
    }
}
</script>

<style>

</style>