<template>
<ol class="breadcrumb mb-0" v-if="pathData.length">
    <li v-for="(path, index) in pathData" class="breadcrumb-item" :class="{ 'active': index === pathData.length - 1 }">
        <a v-if="index < pathData.length - 1" :href="path['url']" v-text="path['caption']"></a>
        <span v-else v-text="path['caption']"></span>
    </li>
</ol>
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