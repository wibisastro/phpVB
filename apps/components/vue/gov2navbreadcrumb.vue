<template>
<span v-if="pathData.length > 1" class="d-flex align-items-center">
    <template v-for="(path, index) in pathData">
        <template v-if="index > 0">
            <i class="bi bi-chevron-right mx-1" style="color:#5b4fb9"></i>
            <a v-if="index < pathData.length - 1" :href="path['url']" style="color:#5b4fb9; text-decoration:none" v-text="path['caption']"></a>
            <span v-else style="color:#5b4fb9" v-text="path['caption']"></span>
        </template>
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