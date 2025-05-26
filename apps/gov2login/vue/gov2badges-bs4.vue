<template>
    <span v-if="isStatus"
          :class="getClass"
          :data-test="`badge-status-${row.id}`">
        {{ row[rowKey] }}
    </span>
</template>

<script>
    module.exports = {
        name: "gov2badges-bs4",
        props: {
            row: Object,
            rowKey: String,
            success: {
                type: Array,
                default: function () {
                    return ['ready', 'paid', 'on'];
                }
            },
            warning: {
                type: Array,
                default: function () {
                    return ['pending-edit', 'pending-add', 'pending-remove'];
                }
            },
            danger: {
                type: Array,
                default: function () {
                    return ['suspended'];
                }
            },
            normal: {
                type: Array,
                default: function () {
                    return ['suspended', 'off'];
                }
            }
        },
        computed: {
            isStatus: function()
            {
                return this.rowKey === 'status';
            },
            getClass: function () {
                if (this.success.includes(this.row[this.rowKey]))
                {
                    return 'badge badge-success'
                }
                if (this.warning.includes(this.row[this.rowKey]))
                {
                    return 'badge badge-warning'
                }
                if (this.danger.includes(this.row[this.rowKey]))
                {
                    return 'badge badge-danger'
                }
                if (this.normal.includes(this.row[this.rowKey]))
                {
                    return 'badge badge-secondary'
                }
            }
        }
    }
</script>

<style scoped>

</style>