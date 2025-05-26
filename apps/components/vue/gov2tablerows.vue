<template>
   <div>
    <div v-if="rows > 1 && !noscroll">
        <span class="button">{{ rows }} of total &nbsp;
            <span v-if="totalRows > 0"> {{ totalRows }} </span>
            <span v-if="totalRows == 0"> 
                <img src="../../images/working.gif"> 
            </span> &nbsp;
           row(s) 
            <span v-if="scrolls > 0"> in {{ scrolls }} scroll(s)</span>
            <span v-if="scrolls == 0"> 
                &nbsp;<img src="../../images/working.gif">  
            </span> &nbsp;
         </span>
    </div>
    <div v-if="rows > 1 && noscroll">
        <span class="button">{{ rows }} row(s)</span>
    </div>
 </div>
</template>
<script>
module.exports = {
    name: 'gov2tablerows',
    props: {
        instance: String,
        noscroll: Boolean 
    },
    data: function () {
        return {
            rows:0,
            totalRows:0,
            scrolls:0,
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
    }
}
</script>
<style></style>