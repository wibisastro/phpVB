<template>
<div :class="{'box' : isHorizontal}" v-if="isActive">
    <div class="field" v-for="path in pathData" :class="{'is-horizontal' : isHorizontal}">
      <div :class="{'field-label' : isHorizontal}">
        <label class="label">{{ setLevel(path['level_label']) }}</label>
      </div>
      <div class="field-body">
        <div class="field">
          <div class="control">
            <a :class="{'button is-light' : isHorizontal}" @click="getBack(path['id'])" v-text="path['caption']"></a>
          </div>
        </div>
      </div>
    </div>
</div>
</template>

<script>
module.exports = {
    name: 'breadcrumbd',
    props: {
        pathUrl: String,
        isHorizontal: Boolean,
        instance: {
            type: String,
            default: ""
        },
        urlListener: String
    },
    data () {
        return {
            pathData: [],
            isActive: false,
            onInstance: ""
        }
    },
    methods: {
        setLevel: function (data) {
            return data.toUpperCase();
        },
        loadData: function(data) {
            if (data) {
                this.pathData=Array.from(Object.keys(data), k=>data[k]);
            }
            if (this.pathData.length > 0) {this.isActive=true;}
            else {this.isActive=false;}
//            eventBus.$emit('loadingStart');
        },
        getData: function(id) {
//            eventBus.$emit('loadingStart');
            if (id) {url=this.pathUrl+'/'+id}
            else {url=this.pathUrl;}
            
            let printUrl;
            let linger=url.indexOf('-1');
            let reset=url.indexOf('-2')
            if ( linger>0 || reset>0 ) {
                printUrl="Invalid";
            } else {
                if (this.urlListener) {
                    //printUrl=url.replace('/', "");
                    printUrl=url;
                } else {
                    printUrl=url.replace(this.instance+'/', "");    
                }
            }
            eventBus.$emit('printUrl'+this.urlListener,printUrl);
            axios.get(url)
                .then(response => this.loadData(response.data))
                .catch(error => this.onGetDataFail(error.response.data));
        },
        onGetDataFail: function(data) {
            eventBus.$emit('openNotif',data);
//            eventBus.$emit('loadingDone');
        },
        getBack: function(data) {
            if (this.instance) {
                eventBus.$emit('setInstance',this.instance);
                eventBus.$emit('setGetUrl',this.instance);
            }
            if (data==0) {data=-2}
            eventBus.$emit('getChildren'+this.instance,data);
            this.getData(data);
        },
        activeInstance: function (data) {
            this.onInstance=data;
        },
    },
    created: function () { 
    //    if (this.instance) {
            this.getData(-1);        
    //    } else {
    //        this.getData();   
    //    }
        eventBus.$on('refreshPath'+this.instance, this.getData);
        eventBus.$on('onInstance', this.activeInstance);
    }
}
</script>

<style>

</style>