<template>
<div class="select">
    <select data-test="input-role" class="form-control"
            v-model="role" v-if="proses">
        <option 
            v-for="opt in options" v-bind:key="opt.id"
            :value="opt.role">
            {{ opt.role }}
        </option>
    </select>
</div>
</template>

<script>
    module.exports = {
        name: "role",
        props: ['value'],
        data: function () {
            return {
                role: this.value,
                getUrl: "/gov2login/member",
                options: {},
                proses: false,
            }
        },
        methods: {
            initialize: function () {
                axios.get(`${this.getUrl}/roleBrowse`)
                    .then(response => {
                        if (response.data)
                        {
                            this.options = response.data;
                            this.proses = true;
                        }
                    })
                    .catch(err => eventBus.$emit('openNotif', err.response.data))
            }
        },
        created: function () {
            this.initialize();
        },
        watch: {
            value: function(){
                this.role = this.value;
            },
            role: function () {
                this.$emit('input', this.role);
            }
        }
    }
</script>

<style scoped>

</style>
