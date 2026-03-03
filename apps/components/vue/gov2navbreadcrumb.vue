<template>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li v-for="(path, index) in pathData" class="breadcrumb-item" :class="{ 'active': index === pathData.length - 1 }">
            <a v-if="index < pathData.length - 1" :href="path['url']" v-text="path['caption']"></a>
            <span v-else v-text="path['caption']"></span>
        </li>
    </ol>
</nav>
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
            isActive: false,
        }
    },
    methods: {
        loadData: function(data) {
            this.pathData=Array.from(Object.keys(data), k=>data[k]);
        },
        getData: function(id) {
            if (id) {url=this.pathUrl+'/'+id}
            else {url=this.pathUrl;}
            axios.get(url)
                .then(response => this.loadData(response.data))
                .catch(error => this.onGetDataFail(error.response));
        },
        onGetDataFail: function(data) {
            eventBus.$emit('openNotif',data);
        }
    },
    created: function () {
        this.getData();
    }
}
</script>

<style>

</style>