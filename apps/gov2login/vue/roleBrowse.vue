<template>
<div>
    <div class="select" @change="setRole">
        <select v-model="role">
        <option value="null" disabled>Tentukan Role</option>
        
        <option v-for="(val, key) in listData" v-bind:key="key"
                    :value="val">
                {{ val }} 
            </option>
        </select>
    </div>    
</div>
</template>

<script>

module.exports = {
    name: 'roleBrowse',
    data: function() {
        return {
            role: "",
            listData: []
        }
    },
    methods: { 
        getData: function () {
            axios.get('/gov2login/user/role/roleBrowse')
                .then(response => this.loadData(response.data))
                .catch(error => eventBus.$emit('openNotif',error.response.data));
        },
        loadData: function(data) {
            this.listData=Array.from(Object.
            keys(data), k=>data[k]);
        },
        setRole: function () {
            this.$emit('input', this.role);
        }
    },
    created: function () {
        this.getData();
    }
}
</script>

<style>

</style>