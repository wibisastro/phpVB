<template>
    <div class="form-inline" :class="position" v-if="scrolls > 1 && isActive">
        Scrolls&nbsp;
      <select v-model="scroll" @change="setScroll()" class="form-control">
        <option disabled="disabled">Scrolls</option>
        <option v-for="item in scrolls" :value="item">{{ item }}</option>
      </select>
    </div>
</template>
<script>
module.exports = {
    name: 'gov2scroll-bs4',
    props: {
        isActive :Boolean,
        instance: {
            type: String,
            default: ''
        },
        position: {
          type: String,
          default: 'float-left'
        }
    },
    data: function () {
        return {
            scroll:1,
            scrolls: 0
        }
    },
    methods: {
        setScrolls(data) {
            console.log(data);
            this.scrolls=data;
        },
        setScroll() {
            eventBus.$emit(`scroll${this.instance}`, this.scroll);
            /*
            this.setFirstPage(this.scroll*this.scrollInterval-this.scrollInterval+1);
            */
        },
    },
    created: function () {
        eventBus.$on('setScroll' , this.setScrolls);
    }
}
</script>
<style></style>