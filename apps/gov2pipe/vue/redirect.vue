<template>
<div></div>
</template>

<script>
module.exports = {
  name: "redirect",
  props: {
    success: Boolean,
    loginUrl: {
      type: String,
      default: '/gov2pipe'
    }
  },
  methods: {
    getParameterByName(name, url = this.$window.location.href) {
      name = name.replace(/[\[\]]/g, '\\$&');
      const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
          results = regex.exec(url);
      if (!results) return null;
      if (!results[2]) return '';
      return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
  },
  mounted() {
    if (this.success) {
      const vm = this;
      const next = this.getParameterByName('next');
      if(next !== null) {
        eventBus.$emit('openNotif',
            {class: 'success',
              notification: 'Redirecting to the next URL in 3 seconds',
              callback: 'infoSnackbar'});
        setTimeout(function () {
          vm.$window.location.href = next;
        }, 3000);
      }
    } else {
      eventBus.$emit('openNotif',
          {class: 'danger',
            notification: 'Login failed, redirecting to login page in 3 seconds',
            callback: 'infoSnackbar'});
      setTimeout(function () {
        vm.$window.location.href = vm.loginUrl;
      }, 3000);
    }
  }
}
</script>