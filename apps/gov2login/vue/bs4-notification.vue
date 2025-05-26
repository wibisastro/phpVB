<template>
<div>
    <!-- <gov2session></gov2session> -->
</div>
</template>

<script>

module.exports = {
    name: 'bs4-notification',
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
            data.class = this.convertToBs4Style(data);
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
            this.toast('b-toaster-bottom-right',data['class']);
        },
        errorSnackbar: function(data) {
            this.toast('b-toaster-bottom-right','danger');
        },
        openToastFull: function(data) {
            this.toast('b-toaster-top-full','info');
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
        convertToBs4Style(data) {
          let _class = '';
          if (data && data.hasOwnProperty('class')) {
             const splitted = data.class.split('-');
             _class = splitted.length > 1 ? splitted[1] : splitted[0];
          }
          return _class === 'primary' ? 'success' : _class;
        }
    },
    created: function () {
        eventBus.$on('openNotif', this.openNotif);
    }
}

</script>