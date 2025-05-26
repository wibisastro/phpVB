<template>
    <div class="float-left form-inline" v-if="options > 1 && isActive">
        Scrolls&nbsp;
        <b-form-select
                v-model="scroll"
                :options="options"
                @change="setScroll">
        </b-form-select>
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
        }
    },
    data: function () {
        return {
            scroll: 1,
            scrolls: 0
        }
    },
    methods: {
        setScrolls(data) {
            this.scrolls=data;
        },
        setScroll() {
            eventBus.$emit(`scroll${this.instance}`, this.scroll);
            /*
            this.setFirstPage(this.scroll*this.scrollInterval-this.scrollInterval+1);
            */
        },
    },
    computed: {
        options: function () {
            let options = [];
            let scrolls = this.scrolls;
            let int = 1;
            if (scrolls !== Infinity && scrolls > 0) {
                while (scrolls--) {
                    options.push({value: int, text: int})
                    int++;
                }
            }
            return options;
        }
    },
    created: function () {
        eventBus.$on('setScroll' , this.setScrolls);
    }
}
</script>
<style></style>