<template>
<div class="notification is-info">
    <a :href="host+url" target="_blank" class="button is-info" v-if="url!='Invalid'">{{ host }}{{ url }}</a>
    <span v-if="url=='Invalid'">Klik link di bawah ini untuk tampilkan URL API</span>
</div>
</template>
<script>
module.exports = {
    name: 'gov2apiurl',
    props: {
        instance: String,
        apihost: String
    },
    data: function() {
        return {
            url: ''
        }
    },
    computed: {
        host: function () {
            let link=[];
            if (this.apihost) {
//                link=this.apihost.match(/.*\//);
            } else {
                link=window.location.href.match(/.*\//);
            }
            return link[0];
        }  
    },
    methods: {
        printUrl: function(data) {
            this.url=data;
        }
    },
    created: function () {
        if (this.instance) {
            eventBus.$on('printUrl'+this.instance, this.printUrl);    
        } else {
            eventBus.$on('printUrl', this.printUrl);   
        }
    }
}
</script>
<style></style>