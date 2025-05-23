<template>
    <nav aria-label="Item per Page">
        <div class="float-left form-inline">
            Per Page: &nbsp;
            <b-form-select
                    v-model="selected"
                    :options="options"
                    @change="setItemPerPage"
                    :data-test="`itemperpage${'-' + instance}`">
            </b-form-select>
        </div>
    </nav>
</template>

<script>
module.exports =  {
    name: "gov2itemperpage-bs4",
    props: {
        interval: Array,
        currentPage: Number,
        instance: {
            type: String,
            default: ''
        }
    },
    data: function () {
        return {
            selected: 0
        }
    },
    methods: {
        setItemPerPage: function () {
            eventBus.$emit(`setItemPerPage${this.instance}`, this.selected);
            eventBus.$emit('setCurrentPage', 1);
            eventBus.$emit('changepage', 1);
        }
    },
    computed: {
        options: function () {
            let options = [];
            this.interval.forEach(val => {
                options.push({value: val, text: val})
            })
            return options;
        }
    },
    created: function () {
        this.selected = this.interval[0];
    }
}
</script>