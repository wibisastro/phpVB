<template>
    <div>
        <div v-if="rows > 1 && !noscroll">
        <span class="btn btn-light">{{ rows }} of total
            <span v-if="totalRows > 0 && searchRows == false">{{ totalRows }}</span>
            <span v-if="totalRows > 0 && searchRows == true">{{ rows }}</span>
            <span v-if="totalRows == 0">
                <img src="../../images/working.gif">
            </span>row(s)
            <span v-if="scrolls > 0"> in {{ scrolls }} scroll(s)</span>
            <span v-if="scrolls == 0">
                &nbsp;<img src="../../images/working.gif">
            </span> &nbsp;
         </span>
        </div>
        <div v-if="rows > 1 && noscroll">
            <span class="btn btn-light">{{ rows }} row(s)</span>
        </div>
    </div>
</template>
<script>
    module.exports = {
        name: 'gov2tablerows-bs4',
        props: {
            instance: String,
            noscroll: Boolean
        },
        data: function () {
            return {
                rows:0,
                totalRows:0,
                scrolls:0,
                searchRows:false
            }
        },
        methods: {
            setRows(data) {
                this.rows=data;
            },
            setTotalRecord(data) {
                this.totalRows=data['totalRecord'];
            },
            setScrolls(data) {
                this.scrolls=data;
            },
            setSearchRows(){
                this.searchRows = true;
            }
        },
        created: function () {
            if (typeof this.instance === 'undefined') {
                eventBus.$on('setRows', this.setRows);
                eventBus.$on('setTotalRecord', this.setTotalRecord);
            } else {
                eventBus.$on('setRows'+this.instance, this.setRows);
                eventBus.$on('setTotalRecord'+this.instance, this.setTotalRecord);
            }
            eventBus.$on('setScroll', this.setScrolls);
            eventBus.$on('setSearchRows', this.setSearchRows);
        }
    }
</script>
<style></style>