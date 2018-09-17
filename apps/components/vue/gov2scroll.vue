<template>
    <div class="select" v-if="scrolls>1">
      <select v-model="scroll" @change="setScroll()">
        <option disabled="disabled">Scrolls</option>
        <option v-for="item in scrolls" :value="item">scroll {{ item }}</option>
      </select>
    </div>  
</template>
<script>
module.exports = {
    name: 'gov2scroll',
    data: function () {
        return {
            scroll:1,
            scrolls: 0
        }
    },
    methods: {
        setScrolls(data) {
            this.scrolls=data;
        },
        setScroll() {
            eventBus.$emit('scroll',this.scroll);
            /*
            this.setFirstPage(this.scroll*this.scrollInterval-this.scrollInterval+1);
            */
        },
        setRows(data) {
            this.rows=data;
        },
    },
    created: function () {
        eventBus.$on('setScroll', this.setScrolls);
    }
}
</script>
<style></style>