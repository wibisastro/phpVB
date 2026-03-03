<template>
<div>
    <span v-if="taggedData != 'empty'"
        class="badge rounded-pill"
        :class="tagCloseable ? 'bg-info' : 'bg-warning text-dark'"
        :title="setDesc(taggedData)">
        {{ setLabel(taggedData[caption]) }}
        <button type="button" class="btn-close btn-close-white ms-1" style="font-size:0.5em;" v-if="tagCloseable" @click="unSetTag(taggedData['id'],taggedData['source_id'])"></button>
    </span>
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
