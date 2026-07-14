<template>
<div class="input-group" style="width: 32ch">
    <input class="form-control" type="text" placeholder="Search..." v-model="searchQuery" @keyup="setQuery">
</div>
</template>
<script>
// Port Vue 3 dari apps/components/vue/gov2search.vue (#6118 3b).
import eventBus from '../eventBus.js'

export default {
    name: 'gov2search',
    data: function () {
        return {
            searchQuery: ''
        }
    },
    methods: {
        setQuery: function () {
            eventBus.$emit('searchQuery', this.searchQuery);
        }
    }
}
</script>
