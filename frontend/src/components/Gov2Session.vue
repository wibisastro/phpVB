<template>
<div></div>
</template>
<script>
// Port Vue 3 dari apps/components/vue/_gov2session.vue (#6118 3b).
// Perubahan sadar: this.$dialog / this.$snackbar (API Buefy, Vue 2 only)
// diganti window.prompt + eventBus openNotif. Jalur ini tidak punya emitter
// di repo (tidak ada yang emit 'openCreateSession') — dipertahankan untuk
// paritas kontrak event.
import eventBus from '../eventBus.js'

export default {
    name: 'gov2session',
    data: function() {
        return {
            action: '',
            fields: [{  "name": "cmd",
                        "value": "session",
                        "label": "Create Session"
                    }, {
                        "name": "token",
                        "label": "Service: ",
                        "placeholder": "Token"
                    }],
            form: new Form(this.fields),
        }
    },
    methods: {
        openCreateSession: function (data) {
            var value = window.prompt(this.fields[1]['label'] + data['server']);
            if (value !== null) {
                this.submit(value);
            }
        },
        submit: function(data) {
//            doSubmit
        },
        openForm: function(notif) {
            eventBus.$emit('openNotif', notif);
        },
        toggleForm: function () {
            this.form['cmd'] = 'add';
            this.form.reset();
        },
        doSubmit: function () {
            this.form.submit('post', this.action)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
        responseBox: function (data) {
            eventBus.$emit('responseBox', data);
        },
        refreshBrowser: function () {
            location.reload();
        },
        formSuccess: function (data) {
            eventBus.$emit('openNotif', data);
            if (data['callback']) { this[data['callback']](data); }
        },
        formFail: function (data) {
            eventBus.$emit('openSnackbar', data);
            if (data['callback']) { this[data['callback']](); }
        },
        setFields: function () {
            this.form['cmd'] = this.fields[0]['value'];
        },
    },
    created: function () {
        this.setFields();
        eventBus.$on('openCreateSession', this.openCreateSession);
    }
}
</script>
