<template>
<div></div>
</template>
<script>
module.exports = {
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
            this.$dialog.prompt({
                message: this.fields[1]['label']+data['server'],
                inputAttrs: {
                    placeholder: this.fields[1]['placeholder'],
                    maxlength: 1000
                },
                onConfirm: (value) => this.submit(value)
            })
        },
        submit: function(data) {
//            doSubmit
        },
        openForm: function(notif) {
            this.$snackbar.open({
                duration: 50000,
                message: notif['notification'],
                type: notif['class'],
                position: 'is-bottom-right',
                actionText: 'Create Session',
                onAction: () => {
                    
                }
            })
        },

        toggleForm: function () {
            this.form['cmd'] = 'add';
            this.submit = 'Add';
            this.form.reset();
        },
        doSubmit: function () {
            this.form.submit('post',this.action)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
        responseBox: function (data) {
            eventBus.$emit('responseBox',data);
        },
        refreshBrowser: function () {
            location.reload();  
        },
        formSuccess: function (data) {
//            eventBus.$emit('refreshData',data['parent_id']);
            eventBus.$emit('openNotif',data);
            if (data['callback']) {this[data['callback']](data);}
        },
        formFail: function (data) {
            eventBus.$emit('openSnackbar',data);
            if (data['callback']) {this[data['callback']]();}
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
<style></style>