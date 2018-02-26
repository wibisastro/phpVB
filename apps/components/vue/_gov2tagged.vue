<template>
    <b-tag v-if="taggedData != 'empty'" rounded
        :type="tagCloseable ? 'is-info' : 'is-warning'"
        :closable="tagCloseable"
        @close="unSetTag(taggedData['id'],taggedData['source_id'])">
        <b-tooltip :label="setDesc(taggedData)" multilined v-text="setLabel(taggedData['target_nama'])">
        </b-tooltip>
    </b-tag>
</template>

<script>
module.exports = {
    name: 'gov2tagged',
    props: {
        taggedData: Object,
        tagCloseable: Boolean
    },
    data() {
        return {
            
        }
    },
    methods: {
        setDesc: function(data) {
            var result="(id:" + data['target_id'] + ") " + data['target_nama'];
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