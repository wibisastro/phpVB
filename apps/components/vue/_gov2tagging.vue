<template>
<div>
    <b-dropdown :class="{ 'is-active' : isActive}" class="is-bottom-left">
        <button class="button is-primary" slot="trigger" @click="getData()">
            <span>Tags</span>
            <b-icon icon="menu-down"></b-icon>
        </button>

        <b-dropdown-item v-for="(item,key) in gridData" :key="key"  @click="setTag('setTag',source_id,item['id'])">
            {{ item[tagCaption] }}
        </b-dropdown-item>
    </b-dropdown>
</template>

<script>
module.exports = {
    name: 'gov2tagging',
    props: {
        getUrl: String,
        postUrl: String,
        source_id: Number,
        tagCaption: String,
        instance: {
            type: String,
            default: ""
        },
        tagLimit: Number
    },
    data() {
        return {
            isActive: false,
            gridData: [],
            form: new Form()
        }
    },
    methods: {
        /*
        selectedTag: function (data) {
            for (let row in this.taggedData) {
                if (this.taggedData[row]['target_id']==data) {
                    return true;
                } else {
                    return false;
                }   
            }
        },
        */
        onTagLimit: function () {
            if (this.tagLimit) {
                if (this.gridData.length <= this.tagLimit) {
                    return true;
                } else {
                    return false;   
                }
            } else {
                return true;
            }
        },
        formSuccess: function (data) {
            eventBus.$emit('refreshData'+this.instance);
            eventBus.$emit('refreshTags'+this.instance);
            eventBus.$emit('openNotif',data);
            if (data['callback']) {this[data['callback']]();}
        },
        formFail: function (data) {
            eventBus.$emit('openNotif',data);
            if (data['callback']) {this[data['callback']]();}
        },
        setTag: function (cmd,source_id,target_id,id="") {
            this.isActive=false;
            this.form['cmd']=cmd;
            this.form['source_id']=source_id
            if (cmd == "setTag") {
                this.form['target_id']=target_id;                
            } else {
                this.form['id']=id;    
            }
            this.form.submit('post',this.postUrl)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
        getData: function () {
            if (this.isActive == false) {
                this.isActive=true;
                url=this.getUrl+'/table/1/-1';
                axios.get(url)
                    .then(response => this.loadData(response.data))
                    .catch(error =>  eventBus.$emit('openNotif',error.response.data));
            } else {
                this.isActive=false;
            }
        },
        unSetTag: function(data) {
            this.setTag('unSetTag',this.source_id,0,data);
        },
        loadData: function(data) {
            this.gridData=Array.from(Object.keys(data), k=>data[k]);
        },
    },
    created: function() {
        eventBus.$on('unSetTag'+this.source_id, this.unSetTag);
    }
}
</script>