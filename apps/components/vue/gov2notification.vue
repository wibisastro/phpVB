<template>
<div>
    <div class="alert" v-if="isNotif" :class="notifClass">
      <button type="button" class="btn-close float-end" @click="isNotif=false"></button>
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
        toAlertClass: function(cls) {
            const map = {
                'is-success': 'alert-success',
                'is-danger':  'alert-danger',
                'is-warning': 'alert-warning',
                'is-info':    'alert-info',
            };
            return map[cls] || cls || 'alert-info';
        },
        showAlert: function(data, autoDismiss) {
            this.isNotif = true;
            this.notifText = data['notification'];
            this.notifClass = this.toAlertClass(data['class']);
            if (autoDismiss) {
                setTimeout(() => { this.isNotif = false; }, 5000);
            }
        },
        openNotif: function(data) {
            let snackbarCallbacks = ['infoSnackbar','openSnackbar','toggleForm','resetButton','confirmClose'];
            if (data['callback']) {
                if (snackbarCallbacks.includes(data['callback'])) {
                    this.showAlert(data, true);
                } else if (data['callback'] == 'openSnackbar') {
                    this.showAlert(data, true);
                } else if (data['callback'] == 'errorSnackbar') {
                    this.showAlert(data, false);
                } else if (data['callback'] == 'openErr') {
                    this.showAlert(data, false);
                }
            } else {
                this.showAlert(data, false);
            }
        },
        infoSnackbar: function(data) {
            this.showAlert(data, true);
        },
        openSnackbar: function(data) {
            this.showAlert(data, true);
        },
        errorSnackbar: function(data) {
            this.showAlert(data, false);
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