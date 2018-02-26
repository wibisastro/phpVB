<template>
<div>
    <div class="dropdown" :class="{ 'is-active' : isActive}">
      <div class="dropdown-trigger">
        <button class="button" @click="getData()">
          <span>Tags</span>
          <span class="icon is-small">
            <i class="fa fa-angle-down" aria-hidden="true"></i>
          </span>
        </button>
      </div>
      <div class="dropdown-menu">
        <div class="dropdown-content">
            <div v-for="item in gridData">
              <div class="dropdown-item" @click="setTag('setTag',source_id,item['id'])">
                <a>{{ item['nama'] }}</a>
              </div>
              <hr class="dropdown-divider">
            </div>
            <a class="dropdown-item" @click="isActive=false">Close</a>
        </div>
      </div>
    </div>
</div>
</template>

<script>
module.exports = {
    name: 'gov2tagging',
    props: {
        getUrl: String,
        postUrl: String,
        source_id: Number,
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