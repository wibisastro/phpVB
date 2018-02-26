<template>
<div class="field has-addons is-grouped">
  <p class="control" v-for="item in tags">
    <a class="button" @click="setTag(source_id,item['id'])" :class="{ 'is-info': setChecked(source_id,item['id']) }">
      <span class="icon">
        <i class="fa fa-check"></i>
      </span>
        <span>{{ item['nama'] }}</span>
      <span v-if="counter<tags.length">{{ getTag(source_id,item['id']) }}</span>
    </a>
  </p>
</div>
</template>

<script>
module.exports = {
    name: 'gov2checkbox',
    props: {
        postUrl: String,
        source_id: Number,
        parent_id: Number,
        tags: Array,
        tagUrl: String,
        tagLimit: Number,
        instance: String
    },
    data() {
        return {
            isActive: true,
            form: new Form(),
            tagged: [],
            counter: 0
        }
    },
    
    watch: {
        parent_id: function () {
            this.getTaggedData();
        }
    },
    methods: {
        getTag: function (source_id,target_id) {
            this.counter=this.counter+1;
            if (this.tags.length<=this.counter) {
                this.getTaggedData();
                this.setChecked(source_id,target_id);    
            }
        },
        getTaggedData: function () {
            var config = {	
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control' : 'no-cache'
                }
            }
            url=this.tagUrl+'/getTags/-1';
            axios.get(url,config)
                .then(response => this.loadTaggedData(response.data))
                .catch(error => eventBus.$emit('openNotif',error.response.data));
        },
        loadTaggedData: function(data) {
            this.tagged=Array.from(Object.keys(data), k=>data[k]);
        },
        setChecked: function (source_id,target_id) {
            let checked=false;
            for (let row in this.tagged) {
                if (this.tagged[row]['source_id']==source_id) {
                    for (let row2 in this.tags) {
                        if (this.tags[row2]['id']==this.tagged[row]['target_id'] && this.tagged[row]['target_id']==target_id) {
                            checked=true;
                            break;
                        }
                    }
                }
            }
            return checked;
        },
        checkTag: function (source_id,target_id) {
            for (let row in this.tagged) {
                if (this.tagged[row]['target_id']==target_id && this.tagged[row]['source_id']==source_id) {
                    return this.tagged[row]['id'];
                }   
            }
        },
        formSuccess: function (data) {
            this.counter=0;
            eventBus.$emit('refreshData'+this.instance);
            eventBus.$emit('openNotif',data);
            if (data['callback']) {this[data['callback']]();}
        },
        formFail: function (data) {
            eventBus.$emit('openNotif',data);
            if (data['callback']) {this[data['callback']]();}
        },
        setTag: function (source_id,target_id) {
            let counter=0;
            let valid=false;
            this.form['source_id']=source_id;
            let id=this.checkTag(source_id,target_id);
            if (id) {
                this.form['id']=id;
                this.form['cmd']="unSetTag";
                valid=true;
            } else {
                for (let row in this.tagged) {
                    if (this.tagged[row]['source_id']==source_id) {
                        counter=counter+1;
                    }   
                }
                if (counter<this.tagLimit) {
                    this.form['target_id']=target_id;
                    this.form['cmd']="setTag";
                    valid=true;
                }
            }
            if (valid==true) {
                this.form.submit('post',this.postUrl)
                    .then(data => this.formSuccess(data))
                    .catch(error => this.formFail(error));
            }
        }
    }
}
</script>
