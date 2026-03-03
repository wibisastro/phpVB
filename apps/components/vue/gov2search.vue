<template>
<div class="input-group">
    <input class="form-control" type="text" placeholder="Search..." v-model="searchQuery" @keyup="setQuery">
</div>
</template>
<script>
module.exports = {
    name: 'gov2search',
    data: function () {
        return {
            searchQuery:''
        }
    },
    methods: {
        setQuery: function () {
            eventBus.$emit('searchQuery',this.searchQuery);
        }
    }
}
</script>
<style></style>