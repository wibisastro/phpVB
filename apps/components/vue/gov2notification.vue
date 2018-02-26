<template>
<div>
    <div class="notification" v-if="isNotif" :class="notifClass">
      <button class="delete" @click="isNotif=false"></button>
        <p v-text="notifText"></p>
    </div>
    <gov2session></gov2session>
</div>
</template>
<script>
module.exports = {
    name: 'gov2notification',
    components: {
        'gov2session': httpVueLoader('./_gov2session.vue'),
    },
    data: function() {
        return {
            isNotif:false,
            notifClass: '',
            notifText: ''
        }
    },
    methods: {
        openNotif: function(data) {
            console.log(data);
            if (data['callback']) {
                if (data['callback']=='openSnackbar') {
                    this.openSnackbar(data);
                } else if (data['callback']=='openErr') {
                    this.isNotif=true;
                    this.notifText=data['notification'];
                    this.notifClass=data['class'];    
                }
            } else {
                this.isNotif=true;
                this.notifText=data['notification'];
                this.notifClass=data['class'];    
            }
            
        },
        openSnackbar: function(data) {
            this.$snackbar.open({
                duration: 50000,
                message: data['notification'],
                type: data['class'],
                position: 'is-bottom-right',
                actionText: 'Create Session',
                onAction: () => {
                    eventBus.$emit('openCreateSession',data['server'])
                }
            })
        },
        
    },
    created: function () {
        eventBus.$on('openNotif', this.openNotif);
    }
}
</script>
<style></style>