<template>
    <div v-if="rows > 1">
        <span class="button">{{ rows }} Row(s)</span>
    </div>  
</template>
<script>
module.exports = {
    name: 'gov2tablerows',
    props: {
        instance: String  
    },
    data: function () {
        return {
            rows:0
        }
    },
    methods: {
        setRows(data) {
            this.rows=data;
        }
    },
    created: function () {
        eventBus.$on('setRows'+this.instance, this.setRows);
    }
}
</script>
<style></style>