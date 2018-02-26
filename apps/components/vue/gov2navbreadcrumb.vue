<template>
<nav class="breadcrumb has-arrow-separator" aria-label="breadcrumbs">
    <ul>
        <li v-for="path in pathData" :class="{ 'is-active': isActive }">
            <a :href="path['url']" v-text="path['caption']"></a>
        </li>
    </ul>
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