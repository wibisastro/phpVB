<template>
<div>
    <b-tag v-if="taggedData != 'empty'" rounded
        :type="tagCloseable ? 'is-info' : 'is-warning'"
        :closable="tagCloseable"
        @close="unSetTag(taggedData['id'],taggedData['source_id'])">
        <b-tooltip :label="setDesc(taggedData)" multilined v-text="setLabel(taggedData[caption])">
        </b-tooltip>
    </b-tag>
</div>
</template>

<script>
module.exports = {
    name: 'gov2tagged',
    props: {
        tagCaption: String,
        taggedData: Object,
        tagCloseable: Boolean
    },
    computed: {
        caption: function () {
            return 'target_'+this.tagCaption;
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
        },
    }
}
</script>