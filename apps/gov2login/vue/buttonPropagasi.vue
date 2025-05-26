<template>
<div class="control">
    <a class="button is-block is-info" @click="onSubmit" :disabled="isEmpty">
        <span class="icon">
          <i class="fa fa-check"></i>
        </span>
        <span>Propagasi {{ size }} akun tercentang</span>
    </a>
</div>
</template>

<script>

module.exports = {
    name: 'buttonPropagasi',
    props:  {
        action: String,
        role: String
    },
    data: function () {
        return {
            checked:[],
            size: 0,
            total: 0,
            form: new Form()
        }
    },
    computed: {
        isEmpty: function () {
            if (this.size==0) {return true;}
        }
    },
    methods: {
        setChecked: function (data) {
            let checked=[];
            let size=0;
            for (key in data) {
                if (data[key]) {
                    checked[size]=data[key];
                    size++;
                }
            }
            this.checked=checked;
            this.size=size;
        },
        onSubmit: function () {
            eventBus.$emit('loadingStart');
            if (this.size>0) { 
                this.form['cmd']='propagasiChecked';
                this.form['role']=this.role;
                this.form['propagasi']=this.checked;
                this.form.submit('post',this.action)
                    .then(data => this.formSuccess(data))
                    .catch(error => this.formFail(error));
            }
        },
        formSuccess: function (data) {
            this.size=0;
            eventBus.$emit('resetChecked');
            eventBus.$emit('openNotif',data);
            eventBus.$emit('refreshData');
            eventBus.$emit('loadingDone');
        },
        formFail: function (data) {
            eventBus.$emit('openNotif',data);
        },
    },
    created: function () {
        eventBus.$on('setChecked', this.setChecked);
    }
}
</script>

<style>

</style>