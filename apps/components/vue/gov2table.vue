<template>
<div v-if="isActive" class="table-container-outer">
<div class="table-container-fade" v-if="wideTable"></div>
<div class="table-container" :style="{ width: setWideTable() }">
  <table class="table is-striped" width="100%" :class="{ 'is-bordered': isBordered }">
    <thead>
      <tr v-if="headers">
        <th v-for="(val,key) in headers" :colspan="val['colspan']"  :rowspan="isHeader(key)">
            <center>{{ val['caption'] }}</center>
        </th>
      </tr>
      <tr>
        <th  v-if="!headers">
            <input
                type="checkbox"
                v-model="allSelected"
                @change="selectAll"
                :disabled="headers">
        </th>
        <th v-for="key in additionalColumns"
          @click="sortBy(key)"
          :class="{ active: sortKey == key }" v-if="!headers">
            <center>
                {{ columnName(key) }}
              <span class="arrow" :class="sortOrders[key] > 0 ? 'asc' : 'dsc'" v-if="!tagUrl">
              </span>
            </center>
        </th>
        <th v-for="key in additionalColumns" v-if="headers">
            <center>{{ columnName(key) }}</center>
        </th>
        <th v-if="!readonly"></th>
        <th v-if="childComponent">
            <center v-if="childComponent['type'] == 'checkbox'">
                {{ childComponent['instance'] | capitalize }}
            </center>
        </th>
      </tr>
    </thead>
    <tfoot v-if="!isTotal">
      <tr>
        <th>
            <input
                type="checkbox"
                v-model="allSelected"
                @change="selectAll"
                :disabled="headers">
        </th>
        <th v-for="key in additionalColumns"
          @click="sortBy(key)"
          :class="{ active: sortKey == key }">
        <center>
            {{ columnName(key) }}
          <span class="arrow" :class="sortOrders[key] > 0 ? 'asc' : 'dsc'" v-if="!tagUrl">
          </span>
            </center>
        </th>
        <th v-if="!readonly"></th>
        <th v-if="childComponent">
            <span v-if="childComponent['type'] == 'checkbox'">
                {{ childComponent['instance'] | capitalize }}
            </span>
        </th>
      </tr>
    </tfoot>
    <tbody>
      <tr v-for="entry in filteredData" :class="{ 'is-selected': checked[entry['id']] }">
          <td><input
              type="checkbox"
              v-model="checked[entry['id']]"
              :true-value="entry['id']"
                @change="checkedRow" :disabled="isCheckbox(entry['checkbox'])">
          </td>
        <td v-for="(key,index) in columns">
            <a v-if="recursive == true" @click="getChildren(entry['id'])">{{ entry[key] }}</a>
            <!--a v-if="showLink(entry['id'])" @click="getChildren(entry['id'])">{{ entry[key] }}</a-->
            <span v-if="recursive == false">{{ entry[key] }}</span>
            <span v-if="entry['tag'] && entry['tag']['field']==key" class="tag" :class="entry['tag']['color']">{{ entry['tag']['caption'] }}</span> 
            <span v-for="(tags,dataset) in taggedData" v-if="isTaggedData">
                <b-taglist>
                    <span v-for="tag in tags" v-if="tagUrl==dataset">
                        <gov2tagged :source_id="parseInt(entry['id'])" :tagged-data="tag" v-if="tag[instance+'_id'] == entry['id'] && key == dataset" :tag-closeable="tagCloseable" :tag-caption="tagCaption"></gov2tagged>
                    </span>
                    <span v-for="tag in tags" v-if="tagUrl!=dataset">
                        <gov2tagged :source_id="parseInt(entry['id'])" :tagged-data="tag" v-if="tag[instance+'_id'] == entry['id'] && key == dataset" :tag-caption="tagCaption"></gov2tagged>
                    </span>
                </b-taglist>
            </span>
            &nbsp;
        </td>
        <td v-if="!readonly">
            <a class="tag is-warning" @click="edit(entry['id'])">Edit</a>
            <a class="tag is-danger" @click="del(entry['id'])" v-if="entry['children'] == 0 || !entry['children']">Del</a>
            <a class="tag is-warning" @click="hasChildren(entry['children'])" v-if="entry['children'] > 0">Del</a>
        </td>
        <td v-if="childComponent && childComponent['type'] == 'dropdown'">
            <gov2tagging :post-url="postUrl" :get-url="childComponent['instance']" :source_id="parseInt(entry['id'])" :instance="instance" v-if="showTagging(entry['level'],entry['id'])" :tag-limit="tagLimit" :tags="tags" :tag-caption="tagCaption"></gov2tagging>
        </td>
        <td v-if="childComponent && childComponent['type'] == 'checkbox'">
            <gov2checkbox :post-url="postUrl" :source_id="parseInt(entry['id'])" :instance="instance" v-if="showTagging(entry['level'],entry['id'])" :tags="tags" :tag-url="tagUrl" :parent_id="parent" :tag-limit="tagLimit"></gov2checkbox>
        </td>
        <td v-if="childComponent && childComponent['type'] == 'progress'" width="70%">
            <gov2progress :instance="instance" :hi="entry['hi']" :lo="entry['lo']"></gov2progress>
        </td>
      </tr>
    </tbody>
    <tfoot v-if="isTotal">
      <tr>
          <th></th>
        <th v-for="key in isTotal">
            {{ countTotal(key) }}
        </th>
        <th v-if="!readonly"></th>
      </tr>
    </tfoot>
   </table>
</div>
</div>
</template>

<script>
module.exports = {
  name: 'gov2table',
  props: {
    isActive: Boolean,
    getUrl: String,
    postUrl: String,
    tagUrl: String,
    tagsUrl: Array,
    columns: Array,
    filterKey: String,
    readonly: Boolean,
    recursive: Boolean,
    itemPerPage: Number,
    instance: {
        type: String,
        default: ""
    },
    childComponent: Object,
    selected: Number,
    tagCloseable: Boolean,
    showTaggingAtLevel: Number,
    tagLimit: Number,
    taggingId: Number,
    tagCaption: String,
    dynColName: {},
    linkOn: {},
    headers: {},
    isBordered: Boolean,
    isTotal: Array,
    wideTable: Boolean,
  },
  components: {
    'gov2tagging': httpVueLoader('./_gov2tagging.vue'),
    'gov2tagged': httpVueLoader('./_gov2tagged.vue'),
    'gov2checkbox': httpVueLoader('./_gov2checkbox.vue'),
    'gov2progress': httpVueLoader('./_gov2progress.vue'),
    'gov2component': httpVueLoader('./gov2component.vue'),
  },
  data () {
    var sortOrders = {}
    this.columns.forEach(function (key) {
        sortOrders[key] = 1
    });
    return {
        gridData: [],
        records:0,
        sortKey: '',
        sortOrders: sortOrders,
        currentPage: '1',
        scroll: 1,
        parent: 0,
        taggedData: {},
        tags: [],
        checked: {},
        isFullPage: true,
        tableWidth: 500,
        allSelected: false
    }
  },
  computed: {
    additionalColumns: function() {
        var col=this.columns;
        if (this.tagUrl && this.childComponent['type']=='dropdown') {col.push(this.tagUrl);}
        if (this.tagsUrl) {
            for (let row in this.tagsUrl) {
                col.push(this.tagsUrl[row]);
            }
        }
        return col;
    },
    isTaggedData: function () {
        if (Object.keys(this.taggedData).length > 0) {return true;}
    },
    filteredData: function () {
        var currentPage = this.currentPage || '1'
        var sortKey = this.sortKey
        var filterKey = this.filterKey && this.filterKey.toLowerCase()
        var order = this.sortOrders[sortKey] || 1
        var data = this.gridData
        data=this.paging(data);
      if (filterKey) {
        data = data.filter(function (row) {
          return Object.keys(row).some(function (key) {
            return String(row[key]).toLowerCase().indexOf(filterKey) > -1
          })
        })
        data=this.paging(data);
        this.pagination(data.length);
        this.gotoPage(1);
        eventBus.$emit('setCurrentPage',1);
      }
      if (sortKey) {
        data = data.slice().sort(function (a, b) {
          a = a[sortKey]
          b = b[sortKey]
            if (sortKey == 'id') {
                return (b - a) * order;
            } else {
                return (a === b ? 0 : a > b ? 1 : -1) * order
            }
        })
        data=this.paging(data);
      }
      if (currentPage) {
        data = data.filter(function (row) {
          return Object.keys(row).some(function (key) {
            return String(row['page'])==currentPage
          })
        })
      }
      return data
    }
  },
  filters: {
    capitalize: function (str) {
        if (str) {
            return str.charAt(0).toUpperCase() + str.slice(1)   
        }
    }
  },
    mounted() {
        this.$nextTick(function() {
            window.addEventListener('resize', this.setTableWidth);
        })
    },
  methods: {
    isCheckbox: function (data) {
      if (this.headers) {
          return true;
      } else if (data) {
          return false;
      } else {
          return true;
      }
    },
    selectAll: function () {
        var selected = {};
        if (this.allSelected==true) {
            this.filteredData.forEach(function (row) {
                if (row.checkbox) {
                    selected[row.id]=row.id;
                }
            });
            this.checked = selected;
        } else {
            this.checked = {}
        }
        this.checkedRow();
    },
    setWideTable: function () {
        if (this.wideTable) {
            return this.tableWidth + 'px';   
        } else {
            return '100%';
        }
    },
    countTotal: function (key) {
        var total=0;
        this.filteredData.forEach(function (row) {
            total=parseInt(total)+parseInt(row[key]);
        });
        if (!isNaN(total)) {return total;}
        else {return this.filteredData.length;}
    },
    isHeader: function(data) {
        if (this.headers && data==0) {
            return 2;
        } else {
            return 1;
        }
    },
    printLog: function (data) {
        console.log(data);
    },
    checkedRow: function () {
        eventBus.$emit('setChecked',this.checked);
    },
    resetChecked: function () {
        this.checked=[];
        this.checkedRow();
    },
    reload: function() {
        location.reload();  
    },
    loadTags: function(data) {
        this.tags=Array.from(Object.keys(data), k=>data[k]);
    },
    getTags: function (tagUrl) {
        if (this.childComponent['parent_id']) {
            url=tagUrl+'/table/1/'+this.childComponent['parent_id'];
        } else {
            url=tagUrl+'/table/1/-1';
        }
        axios.get(url)
            .then(response => this.loadTags(response.data))
            .catch(error => eventBus.$emit('openNotif',error.response.data));
    },
    showLink: function (source_id) {
        var isShow=true;
        if (this.tagsUrl && this.linkOn) {
            for (let row in this.tagsUrl) {
                if (this.tagsUrl[row] == this.linkOn['field']) {
                    for (let tags in this.taggedData[this.tagsUrl[row]]) {
                        if (this.taggedData[this.tagsUrl[row]][tags]['source_id']===source_id) {
                            if (this.linkOn['field']) {
                                if (this.taggingId == this.taggedData[this.tagsUrl[row]][tags]['target_id']) {
                                    isShow=true;
                                    break;    
                                } else {
                                    isShow=false;
                                }
                            } else {
                                isShow=true;
                                break;
                            }
                        } else {
                            isShow=false;
                        }    
                    }
                } else {
                    isShow=false
                }   
            }
        } else {
            isShow=true;
        }
        return isShow;
    },
    columnName: function (data) {
        let newColName=data;
        if (this.dynColName && this.gridData.length > 0 && this.gridData[0]!='empty') {
            if (data == this.dynColName['col1']) {
                newColName=this.gridData[0][this.dynColName['col2']];
                if (newColName=="") {
                    newColName=data;
                } else {
                    newColName=newColName.charAt(0).toUpperCase() + newColName.slice(1);
                }
            } else {
                newColName=data.charAt(0).toUpperCase() + data.slice(1);
            }
        } else {
            newColName=data.charAt(0).toUpperCase() + data.slice(1);    
        }
        return newColName;
    },
    showTagging: function (level,source_id) {
        var isShow=true;
        if (this.childComponent) {
            if (this.showTaggingAtLevel) {
                if (this.showTaggingAtLevel == level) {
                    isShow=true;
                } else {
                    isShow=false;
                }
            } else if (this.tagsUrl) {
                for (let row in this.tagsUrl) {
                    for (let tags in this.taggedData[this.tagsUrl[row]]) {
                        if (this.taggedData[this.tagsUrl[row]][tags]['source_id']===source_id) {
                            if (this.taggingId) {
                                if (this.taggingId == this.taggedData[this.tagsUrl[row]][tags]['target_id']) {
                                    isShow=true;
                                    break;    
                                } else {
                                    isShow=false;
                                }
                            } else {
                                isShow=true;
                                break;
                            }
                        } else {
                            isShow=false;
                        }    
                    }   
                }
            } else {
                isShow=true;
            }
        } else {
            isShow=false;
        }
        return isShow;
    },
    getTaggedData: function (otherTag) {
        var config = {	
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control' : 'no-cache'
            }
        }
        if (otherTag) {
            url=otherTag+'/getTags/'+this.parent;            
        } else {
            url=this.tagUrl+'/getTags/'+this.parent;
        }
        axios.get(url,config)
            .then(response => this.loadTaggedData(response.data,otherTag))
            .catch(error => this.onGetDataFail(error.response.data));
    },
    loadTaggedData: function(data,otherTag) {
        var _tags=Array.from(Object.keys(data), k=>data[k]);
        var _tagUrl=this.tagUrl;

        if (otherTag) {
            this.taggedData={};
            this.taggedData[otherTag]=_tags;
        } else {
            if (this.taggedData[_tagUrl]) {
                this.taggedData[_tagUrl]={}
                this.taggedData[_tagUrl]=Object.assign(this.taggedData[_tagUrl], _tags);
                var taggedData2=this.taggedData;
                this.taggedData={};
                this.taggedData=taggedData2;
            } else {
                this.taggedData={};
                this.taggedData[_tagUrl]=_tags;
            }
        }
    },
    scrolling: function (data) {
        this.setScroll(data);
        this.getData();
        this.gotoPage(1);
        eventBus.$emit('setCurrentPage',1);
    },
    setParent: function (data) {
        if (data) {
            this.parent = data;
            eventBus.$emit('parent'+this.instance,data);
        }
    },
    setScroll: function (data) {
        this.scroll = parseInt(data);
        eventBus.$emit('pageScroll'+this.instance,this.scroll);
    },
    paging: function (data) {
        var page = 1;
        var number = 1;
        for (let field in data) {
            data[field]['page']=page;
            var mod = number % this.itemPerPage;
            if (mod==0) {page++;}
            number++;
        }
        return data;
    },
    sortBy: function (key) {
        if (!this.tagUrl) {
            this.sortKey = key
            this.sortOrders[key] = this.sortOrders[key] * -1
        }
    },
    gotoPage: function (page) {
        this.currentPage = parseInt(page)
    },
    errorMessage: function (errors) {
        console.log(errors);
    },
    loadData: function(data) {
        this.gridData=Array.from(Object.keys(data), k=>data[k]);
        if (data['data'] == 'empty') {
            this.records=0;        
        } else {
            this.records=this.gridData.length;
            if (this.tagUrl) {
                this.getTaggedData();
            }
            if (this.tagsUrl) {
                for (let row in this.tagsUrl) {
                    this.getTaggedData(this.tagsUrl[row]);   
                }
            }
        }
        
        if (!this.isTotal) {
            this.getTotalRecord(this.parent);
            this.pagination(this.records);
        }
        eventBus.$emit('loadingDone',this.instance);
    },
    pagination: function (records) {
        var pagination=[];
        this.records=records;
        pagination['records']=records;
        pagination['itemPerPage']=this.itemPerPage;
        eventBus.$emit('pagination',pagination);
        eventBus.$emit('setRows'+this.instance,this.records);
    },
    edit: function (id) {
        axios.get(this.getUrl+'/edit/'+parseInt(id))
            .then(response => eventBus.$emit('dataEdit',response.data))
            .catch(error => this.onGetDataFail(error.response.data));
    },
    del: function (id) {
        eventBus.$emit('dataDel',parseInt(id));
    },
    hasChildren: function (data) {
        eventBus.$emit('hasChildren',parseInt(data));
    },
    sayUrl: function (url) {
        let printUrl;
        let linger=url.indexOf('-1');
        let reset=url.indexOf('-2');
        if ( linger>0 || reset>0 ) {
            printUrl="Invalid";
        } else {
            printUrl=url.replace(this.instance+'/', "");
        }
        eventBus.$emit('printUrl'+this.instance,printUrl);
    },
    getData: function(id) {
        eventBus.$emit('loadingStart',this.instance);
        this.setParent(id);
        if (this.parent) {url=this.getUrl+'/table/'+this.scroll+'/'+this.parent;}
        else if (this.recursive) {url=this.getUrl+'/table/'+this.scroll+'/0';}
        else {url=this.getUrl+'/table/'+this.scroll+'/';}
        this.sayUrl(url);
        axios.get(url)
            .then(response => this.loadData(response.data))
            .catch(error => this.onGetDataFail(error.response.data));
    },
    getTotalRecord: function(id) {
        if (this.recursive) {url=this.getUrl+'/count/'+id;}
        else {url=this.getUrl+'/count';}
        axios.get(url)
            .then(response => this.onGetTotalRecordDone(response.data))
            .catch(error => this.onGetDataFail(error.response.data));
    },
    onGetTotalRecordDone: function(data) {
        eventBus.$emit('setTotalRecord'+this.instance,data);
        eventBus.$emit('loadingDone',this.instance);
    },
    getChildren: function (id) {
        this.setScroll(1);
        this.getData(id);
        eventBus.$emit('refreshPath'+this.instance,id);
        axios.get(this.getUrl+'/children/'+id)
            .then(response => eventBus.$emit('setLevel',response.data['level']))
            .catch(error => this.onGetDataFail(error.response.data));
//        eventBus.$emit('toggleForm',false);
        this.gotoPage(1);
        eventBus.$emit('setCurrentPage',1);
    },
    onGetDataFail: function(data) {
        this.gridData=[];
        eventBus.$emit('loadingDone',this.instance);
        eventBus.$emit('openNotif',data);
    },
    setInstance: function() {
        eventBus.$on('getChildren'+this.instance, this.getChildren);
    },
    setTableWidth: function  () {
        this.tableWidth=document.documentElement.clientWidth-220;
    }
  },
  created: function () {
    if (this.recursive) {
        if (this.selected) {
            this.getData(this.selected);
        } else {
            this.getData(-1);
        }        
    } else {
        this.getData();   
    }
    eventBus.$emit('refreshPath'+this.instance,-1);
    eventBus.$on('changepage', this.gotoPage);
    eventBus.$on('formFail', this.errorMessage);
    eventBus.$on('refreshData'+this.instance, this.getData);
    eventBus.$on('setInstance', this.setInstance);
    eventBus.$on('getChildren'+this.instance, this.getChildren);
    eventBus.$on('scroll', this.scrolling);
    if (this.tagUrl) {
        this.getTaggedData();
    }
    if (this.tagsUrl) {
        for (let row in this.tagsUrl) {
            this.getTaggedData(this.tagsUrl[row]);   
        }
    }
    if (this.childComponent) {
        this.getTags(this.childComponent['instance']);
    }
    eventBus.$on('refreshTags'+this.instance, this.getTaggedData);
    eventBus.$on('reload', this.reload);
    eventBus.$on('resetChecked', this.resetChecked);
    this.setTableWidth();
  }
}
</script>

<style>
th.active {
  color: blue;
}

th.active .arrow {
  opacity: 1;
}

.arrow {
  display: inline-block;
  vertical-align: middle;
  width: 0;
  height: 0;
  margin-left: 5px;
  opacity: 0.66;
}

.arrow.asc {
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-bottom: 4px solid blue;
}

.arrow.dsc {
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 4px solid blue;
}
    
.table-container
{
	overflow-y: auto;
	_overflow: auto;
	margin: 0 0 1em;
    width: 100%;
}
    
.table-container::-webkit-scrollbar
{
	-webkit-appearance: none;
	width: 14px;
	height: 14px;
}

.table-container::-webkit-scrollbar-thumb
{
	border-radius: 8px;
	border: 3px solid #fff;
	background-color: rgba(0, 0, 0, .3);
}
    
.table-container-outer { position: relative; }

.table-container-fade
{
	position: absolute;
	right: 0;
	width: 20px;
	height: 100%;
	background-image: -webkit-linear-gradient(0deg, rgba(255,255,255,.5), #fff);
	background-image: -moz-linear-gradient(0deg, rgba(255,255,255,.5), #fff);
	background-image: -ms-linear-gradient(0deg, rgba(255,255,255,.5), #fff);
	background-image: -o-linear-gradient(0deg, rgba(255,255,255,.5), #fff);
	back
</style>


