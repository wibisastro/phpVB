<template>
<span v-if="pathData.length" class="d-flex align-items-center">
    <template v-for="(path, index) in pathData">
        <i class="bi bi-chevron-right small text-muted mx-1"></i>
        <a v-if="index < pathData.length - 1" :href="path['url']" class="text-muted small" v-text="path['caption']"></a>
        <span v-else class="small" v-text="path['caption']"></span>
    </template>
</span>
</template>

<script>
module.exports = {
    name: 'gov2navbreadcrumb',
    props: {
        pathUrl: String
    },
    data () {
        return {
            pathData: [],
        }
    },
    methods: {
        loadData: function(data) {
            this.pathData=Array.from(Object.keys(data), k=>data[k]);
        },
        getData: function() {
            if (!this.pathUrl) return;
            axios.get(this.pathUrl)
                .then(response => this.loadData(response.data))
                .catch(error => {});
        },
    },
    created: function () {
        this.getData();
    }
}
</script>

<style>

</style>