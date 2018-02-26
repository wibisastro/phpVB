<template>
<div class="field has-addons">
  <div class="control">
    <input class="input" type="text" placeholder="Search..." v-model="searchQuery" @keyup="setQuery">
  </div>
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