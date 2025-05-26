<template>
    <b-badge v-if="data" pill
             :variant="tagCloseable ? 'info' : 'warning'"
             v-b-tooltip.hover
             :title="setDesc(data)">
        {{setLabel(data[caption])}} &nbsp;
        <a @click="unSetTag(data['id'],data['source_id'])">
            <i class="remove fa fa-times white"></i>
        </a>
    </b-badge>
</template>

<script>
    module.exports = {
        name: 'bs4tagged',
        props: {
            taggedData: Object,
            tagCloseable: Boolean,
            tagCaption: String
        },
        data() {
            return {
                data: this.taggedData
            }
        },
        computed: {
            caption: function () {
                return `target_${this.tagCaption}`;
            }
        },
        methods: {
            setDesc: function(data) {
                var result="(id:" + data['target_id'] + ") " + data[this.caption];
                return result;
            },
            setLabel: function(data) {
                if (data) {
                    var result=data.split(' ');
                    if (result.length > 1) {
                        return result[0]+"...";
                    } else {
                        return result[0];
                    }
                }
            },
            unSetTag: function(data,source_id) {
                eventBus.$emit('unSetTag'+source_id,data);
                // tambahan rijal
                // this.data = Object;
            },
        }
    }
</script>
<style>
    .badge-pill {
        /*padding-right: .6em;*/
        /*padding-left: .6em;*/
        border-radius: 10rem;
    }

    /* adapted from http://maxwells.github.io/bootstrap-tags.html */
    .tag {
        font-size: 14px;
        padding: .3em .4em .4em;
        margin: 0 .1em;
    }
    .tag a {
        color: #bbb;
        cursor: pointer;
        opacity: 0.6;
    }
    .tag a:hover {
        opacity: 1.0
    }
    .tag .remove {
        vertical-align: bottom;
        top: 0;
    }
    .tag a {
        margin: 0 0 0 .3em;
    }
    .tag a .white {
        color: #fff;
        margin-bottom: 2px;
    }
    i:hover {
        cursor : pointer;
    }
</style>