<template>
<div v-show="isOpen">
    <button class="delete delete_loc" @click="isOpen=false"></button>
    <iframe 
    frameborder="0" 
    allowtransparency="true" 
    :src="formUrl"  
    class="iframe_loc">
    </iframe>  
</div>
</template>

<script>

module.exports = {
    name: 'formfield',
    props: {
        formUrl: String
    },
    data: function () {
        return {
            isOpen: true
        }
    },
    methods: {
        toggleForm: function (data) {
            this.isOpen = data;
        },
    },
    created: function () {
        eventBus.$on('toggleForm', this.toggleForm);
    }
}
</script>

<style>
.delete_loc {
    z-index: 2147483647;	
    position: fixed; 
	right: 0px; 
	top: 0px;
    margin: 10px;
}

.iframe_loc {
    z-index: 2147483646; 
	display: block; 
	background: rgba(0, 0, 0, 0.004); 
	border: 0px none transparent; 
	overflow-x: hidden; 
	overflow-y: auto; 
	visibility: visible; 
	margin: 0px; 
	padding: 0px; 
	-webkit-tap-highlight-color: transparent; 
	position: fixed; 
	left: 0px; 
	top: 0px; 
	width: 100%; 
	height: 100%;
}
</style>