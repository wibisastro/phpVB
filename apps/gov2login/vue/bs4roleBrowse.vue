<template>
<select class="form-control"
        v-model="role"
        @change="setRole">
    <option disabled :value="null">Tentukan Role2</option>
    <option v-for="(val2, key2) in listData"
            v-bind:key="key2"
            :value="val2">
        {{val2}}
    </option>
</select>
</template>

<script>

module.exports = {
    name: 'roleBrowse',
    props: ['value'],
    data: function() {
        return {
            role: this.value,
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
            this.role = this.value;
        },
        setRole: function () {
            this.$emit('input', this.role);
        }
    },
    created: function () {
        this.getData();
    },
    watch: {
        value: function () {
            this.role = this.value;
        },
        role: function () {
            this.$emit('input', this.role);
        }
    }
}
</script>

<style>

</style>