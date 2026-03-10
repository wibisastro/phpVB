<template>
    <div class="d-inline-flex align-items-center position-relative" v-if="isActive">
            <input type="text"
                class="form-control"
                placeholder="Search..."
                v-model="searchQuery"
                @keyup="setQuery" :data-test="`search-box${'-' + instance}`"/>
        <i class="fa fa-search search-icon"></i>
    </div>
</template>

<script>
    module.exports = {
        name: 'gov2search-bs4',
        props: {
            instance: {
                type: String,
                default: ''
            },
            isActive: Boolean
        },
        data: function () {
            return {
                searchQuery: ''
            };
        },
        methods: {
            setQuery: function () {
                eventBus.$emit('searchQuery' + this.instance, this.searchQuery);
            }
        }
    }
</script>
<style scoped>
    .search-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        pointer-events: none;
    }
</style>