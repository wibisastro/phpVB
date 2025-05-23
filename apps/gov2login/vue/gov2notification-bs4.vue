<template>
    <div></div>
</template>

<script>

module.exports = {
    name: 'gov2notification-bs4',
    components: {
        // 'gov2session': httpVueLoader('./_gov2session.vue'),
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
            if (data['callback']) {
                if (data['callback']==='openSnackbar') {
                    this.openSnackbar(data);
                } else if (data['callback']==='errorSnackbar') {
                    this.errorSnackbar(data);
                } else if (data['callback']==='openErr') {
                    this.isNotif=true;
                    this.notifText=data['notification'];
                    this.notifClass=data['class'];  
                    this.errorSnackbar(data);
                } else {
                    this.isNotif=true;
                    this.notifText=data['notification'];
                    this.notifClass=data['class'];  
                    if (data['class']==='is-danger') {
                        this.errorSnackbar(data);
                    } else {
                        this.openSnackbar(data);
                    }
                }
            } else {
                this.isNotif=true;
                this.notifText=data['notification'];
                this.notifClass=data['class'];
                this.openToastFull(data);
            }
            
        },
        openSnackbar: function(data) {
            this.toast('b-toaster-top-right',this.defineClass(data['class']));
        },
        errorSnackbar: function(data) {
            this.toast('b-toaster-top-right','danger');
        },
        openToastFull: function(data) {
            let class_ = 'info';

            if (data.hasOwnProperty('class')) {
                class_ = data.class;
            }
            this.toast('b-toaster-top-full',class_);
        },
        toast(position, variant) {
            this.$bvToast.toast(this.notifText, {
                title: `Notification`,
                toaster: position,
                variant: variant,
                solid: true,
                appendToast: true
            })
        },
        defineClass: function (text) {
            switch (text) {
                case 'info':
                case 'is-info':
                    text = 'info';
                    break;
                case 'warning':
                case 'is-warning':
                    text = 'warning';
                    break;
                case 'danger':
                case 'is-danger':
                    text = 'danger';
                    break;
                case 'success':
                case 'is-success':
                    text = 'success'
                    break;
            }
            return text;
        }
        
    },
    created: function () {
        eventBus.$on('openNotif', this.openNotif);
    }
}

</script>