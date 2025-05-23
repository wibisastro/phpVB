<template>
    <div>
        <field-referensi-kuesioner v-if="isMe('referensi_id')" v-model="content"></field-referensi-kuesioner>
        <field-judul-kuesioner v-if="isMe('judul_kuesioner')" v-model="content"></field-judul-kuesioner>
        <field-datepicker v-if="isMe('date_start')" v-model="content" v-bind:key="`date_start`"></field-datepicker>
        <field-timepicker v-if="isMe('time_start')" v-model="content" v-bind:key="'time_start'"></field-timepicker>
        <field-datepicker v-if="isMe('date_end')" v-model="content" v-bind:key="'date_end'"></field-datepicker>
        <field-timepicker v-if="isMe('time_end')" v-model="content" v-bind:key="'time_end'"></field-timepicker>
    </div>
</template>
<script>
    module.exports = {
        name: 'gov2component',
        props: {
            componentName: String,
            value: '',
            isFilterUnit: {},
            getUrl: String
        },
        data: function() {
            return {
                content: this.value
            }
        },
        methods: {
            isMe: function (fieldName = null) {
                if (!fieldName)
                {
                    return this.useDatePickerFields.includes(this.componentName)
                } else {
                    return this.componentName === fieldName;
                }
            }
        },
        components: {
            'field-referensi-kuesioner': httpVueLoader('./field-referensi-kuesioner.vue'),
            'field-judul-kuesioner': httpVueLoader('./field-judul-kuesioner.vue'),
            'field-datepicker': httpVueLoader('./field-datepicker.vue'),
            'field-timepicker': httpVueLoader('./field-timepicker.vue'),
        },
        watch: {
            value: function() {
                this.content = this.value;
            },
            content: function () {
                this.$emit('input', this.content);
            }
        }
    }
</script>

<style>

</style>