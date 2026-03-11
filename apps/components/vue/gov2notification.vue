<template>
<div>
    <!-- Top-right: warning & danger (offset bawah agar tidak nutupi header/user) -->
    <div class="toast-container position-fixed end-0 p-3" style="z-index: 1080; top: 70px;">
        <div ref="toastTop" class="toast border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header text-white" :class="toastHeaderClass">
                <i class="bi me-2" :class="toastIcon"></i>
                <strong class="me-auto">{{ toastTitle }}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" v-text="notifText"></div>
        </div>
    </div>
    <!-- Bottom-right: success & info -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div ref="toastBottom" class="toast border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header text-white" :class="toastHeaderClass">
                <i class="bi me-2" :class="toastIcon"></i>
                <strong class="me-auto">{{ toastTitle }}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" v-text="notifText"></div>
        </div>
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
            notifText: '',
            notifType: 'info',
            isLoading: false
        }
    },
    computed: {
        toastHeaderClass: function() {
            var map = {
                'success': 'bg-success',
                'danger':  'bg-danger',
                'warning': 'bg-warning',
                'info':    'bg-info',
                'primary': 'bg-primary'
            };
            return map[this.notifType] || 'bg-info';
        },
        toastIcon: function() {
            var map = {
                'success': 'bi-check-circle-fill',
                'danger':  'bi-x-circle-fill',
                'warning': 'bi-exclamation-triangle-fill',
                'info':    'bi-info-circle-fill',
                'primary': 'bi-bell-fill'
            };
            return map[this.notifType] || 'bi-info-circle-fill';
        },
        toastTitle: function() {
            var map = {
                'success': 'Berhasil',
                'danger':  'Error',
                'warning': 'Peringatan',
                'info':    'Info',
                'primary': 'Info'
            };
            return map[this.notifType] || 'Info';
        },
        isTopPosition: function() {
            return this.notifType === 'danger' || this.notifType === 'warning';
        }
    },
    methods: {
        loading: function() {
            var vm = this;
            vm.isLoading = true;
            setTimeout(function() {
                if (vm.isLoading) {
                    vm.isLoading = false;
                    var fail = {};
                    fail['class'] = 'is-danger';
                    fail['notification'] = 'Proses butuh waktu lama, tunggu hingga muncul pop-up';
                    vm.errorSnackbar(fail);
                }
            }, 7000);
        },
        loadingDone: function() {
            this.isLoading = false;
        },
        toNotifType: function(cls) {
            var map = {
                'is-success': 'success',
                'is-danger':  'danger',
                'is-warning': 'warning',
                'is-info':    'info',
                'is-primary': 'primary',
                'success': 'success',
                'danger':  'danger',
                'warning': 'warning',
                'info':    'info',
                'primary': 'primary'
            };
            return map[cls] || 'info';
        },
        showToast: function(data) {
            var vm = this;
            vm.notifText = data['notification'] || '';
            vm.notifType = vm.toNotifType(data['class']);

            // danger = manual close, sisanya auto-hide
            var autoHide = vm.notifType !== 'danger';
            var delay = (vm.notifType === 'warning') ? 7000 : 5000;

            vm.$nextTick(function() {
                var el = vm.isTopPosition ? vm.$refs.toastTop : vm.$refs.toastBottom;
                if (el && typeof bootstrap !== 'undefined') {
                    var existing = bootstrap.Toast.getInstance(el);
                    if (existing) { existing.dispose(); }
                    var opts = { autohide: autoHide, delay: delay };
                    var toast = new bootstrap.Toast(el, opts);
                    toast.show();
                }
            });
        },
        openNotif: function(data) {
            this.showToast(data);
        },
        infoSnackbar: function(data) {
            this.showToast(data);
        },
        openSnackbar: function(data) {
            this.showToast(data);
        },
        errorSnackbar: function(data) {
            this.showToast(data);
        }
    },
    created: function () {
        eventBus.$on('openNotif', this.openNotif);
        eventBus.$on('loadingStart', this.loading);
        eventBus.$on('loadingDone', this.loadingDone);
    }
}
</script>
<style></style>
