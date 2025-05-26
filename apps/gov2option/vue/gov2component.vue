<template>
    <div>
        <role-browse v-if="componentName==='role'" v-model="role"></role-browse>
    </div>
</template>
<script>

    module.exports = {
        name: 'gov2component',
        props: {
            componentName: String,
            value: '',
        },
        data: function() {
            return {
                role: this.value
            }
        },
        components: {
            'role-browse': httpVueLoader('./roleBrowse.vue'),
        },
        watch: {
            value: function() {
                this.role = this.value;
            },
            role: function () {
                this.$emit('input', this.role);
            }
        }
    }
</script>

<style>

</style>