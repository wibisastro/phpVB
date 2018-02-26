<template>
<div>
    <h1 class="title" v-text="title"></h1>
    <h2 class="subtitle" v-text="subtitle"></h2>
</div>
</template>

<script>
module.exports = {
    name: 'headerbar',
    data () {
        return {
            title: 'BulmaH',
            subtitle: 'Kania Equi',
        }
    }
}
</script>

<style>

</style>
