<template>
    <div class="select">
      <select v-model="itemPerPage" @change="setItemPerPage()">
        <option disabled="disabled">Rows</option>
        <option v-for="item in interval" :value="item">{{ item }}</option>
      </select>
    </div>
</template>
<script>
module.exports = {
    name: 'gov2itemperpage',
    props: {
        interval: Array
    },
    data: function () {
        return {
            itemPerPage:0
        }
    },
    methods: {
        setItemPerPage: function () {
            eventBus.$emit('setItemPerPage',this.itemPerPage);
            eventBus.$emit('setCurrentPage',1);
            eventBus.$emit('changepage',1);
        }
    },
    created: function () {
        this.itemPerPage=this.interval[0];
    }
}
</script>
<style></style>