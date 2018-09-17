<template>
<div>
    <div class="notification" v-if="isNotif" :class="notifClass">
      <button class="delete" @click="isNotif=false"></button>
        <p v-text="notifText"></p>
    </div>
    <gov2session></gov2session>
    <b-notification :closable="true" v-if="isLoading">
        <b-loading :is-full-page="true" :active.sync="isLoading"></b-loading>
        Loading...
    </b-notification>
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
            notifText: '',
            isLoading: false
        }
    },
    methods: {
        loading () {
            const vm = this
            vm.isLoading = true
            setTimeout(() => {
                if (vm.isLoading) {
                    vm.isLoading = false;
                    let fail=[]
                    fail['class']="is-danger";
                    fail['notification']="Proses butuh waktu lama, tunggu hingga muncul pop-up";
                    this.errorSnackbar(fail);
                }
            }, 7 * 1000)
        },
        loadingDone () {
            this.isLoading=false;
        },
        openNotif: function(data) {
         //   console.log(data);
            if (data['callback']) {
                if (data['callback']=='infoSnackbar') {
                    this.infoSnackbar(data);
                } else if (data['callback']=='openSnackbar') {
                    this.openSnackbar(data);
                } else if (data['callback']=='errorSnackbar') {
                    this.errorSnackbar(data);
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
        infoSnackbar: function(data) {
            this.$snackbar.open({
                duration: 5000,
                message: data['notification'],
                type: data['class'],
                position: 'is-bottom-right',
                actionText: 'Dismiss'
            })
        },
        openSnackbar: function(data) {
            this.$snackbar.open({
                duration: 5000,
                message: data['notification'],
                type: data['class'],
                position: 'is-bottom-right',
                actionText: 'Dismiss',
                /*
                actionText: 'Create Session',
                onAction: () => {
                    eventBus.$emit('openCreateSession',data['server'])
                }
                */
            })
        },
        errorSnackbar: function(data) {
            this.$snackbar.open({
                duration: 5000,
                message: data['notification'],
                type: data['class'],
                position: 'is-bottom-right',
                actionText: 'OK'
            })
        },
        
    },
    created: function () {
        eventBus.$on('openNotif', this.openNotif);
        eventBus.$on('loadingStart', this.loading);
        eventBus.$on('loadingDone', this.loadingDone);
    }
}
</script>
<style></style>