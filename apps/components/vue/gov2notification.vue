<template>
<div>
    <!-- BS5 Toast container (top-right, fixed) -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div ref="toast" class="toast border-0" :class="toastBgClass" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header" :class="toastBgClass">
                <i class="bi me-2" :class="toastIcon"></i>
                <strong class="me-auto" :class="toastTextClass">{{ toastTitle }}</strong>
                <button type="button" class="btn-close" :class="{'btn-close-white': isWhiteText}" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" :class="toastTextClass" v-text="notifText"></div>
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
        toastBgClass: function() {
            var map = {
                'success': 'bg-success text-white',
                'danger':  'bg-danger text-white',
                'warning': 'bg-warning text-white',
                'info':    'bg-info text-white',
                'primary': 'bg-primary text-white'
            };
            return map[this.notifType] || 'bg-info text-white';
        },
        toastTextClass: function() {
            return 'text-white';
        },
        isWhiteText: function() {
            return true;
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
        showToast: function(data, autoDismiss) {
            var vm = this;
            vm.notifText = data['notification'] || '';
            vm.notifType = vm.toNotifType(data['class']);
            vm.$nextTick(function() {
                var el = vm.$refs.toast;
                if (el && typeof bootstrap !== 'undefined') {
                    var existing = bootstrap.Toast.getInstance(el);
                    if (existing) { existing.dispose(); }
                    var opts = { autohide: !!autoDismiss, delay: 5000 };
                    var toast = new bootstrap.Toast(el, opts);
                    toast.show();
                }
            });
        },
        openNotif: function(data) {
            var snackbarCallbacks = ['infoSnackbar','openSnackbar','toggleForm','resetButton','confirmClose'];
            if (data['callback']) {
                if (snackbarCallbacks.indexOf(data['callback']) !== -1) {
                    this.showToast(data, true);
                } else if (data['callback'] === 'errorSnackbar' || data['callback'] === 'openErr') {
                    this.showToast(data, false);
                }
            } else {
                this.showToast(data, false);
            }
        },
        infoSnackbar: function(data) {
            this.showToast(data, true);
        },
        openSnackbar: function(data) {
            this.showToast(data, true);
        },
        errorSnackbar: function(data) {
            this.showToast(data, false);
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
