<template>
<aside class="menu">
    <div class="menuspace" v-for="mainmenu in menus">
        <div class="menuspace" v-for="menu in mainmenu['menu']">
            <p class="menu-label">
                {{ menu['caption'] }}
            </p>
             <ul class="menu-list">
                <li v-for="item in menu['menu']">
                    <a href="{{ webroot+item['url'] }}">{{ item['caption'] }}</a>
                </li>
            </ul>
        </div>
    </div>
</aside> 
</template>

<script>

module.exports = {
    name: 'gov2menu',
    props: {
        menus: Array,
        webRoot: String,
        pageID: String
    },
    data: function () {
        return {
            menuData: [],
            isActive: false,
        }
    },
    methods: {
        loadData: function(data) {
            this.menuData=Array.from(Object.keys(data), k=>data[k]);
        },
        getData: function(id) {
            if (id) {url=this.menuUrl+'/'+id}
            else {url=this.menuUrl;}
            axios.get(url)
                .then(response => this.loadData(response.data))
                .catch(error => this.onGetDataFail(error.response.data));
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
    .menuspace {
        margin-bottom: 10px;
    }
</style>