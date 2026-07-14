<template>
<div v-if="isActive" class="table-container-outer">
<div class="table-container-fade" v-if="wideTable"></div>
<div class="table-container" :style="{ width: setWideTable() }">
  <table class="table table-striped table-hover" :class="{ 'table-bordered': isBordered }">
    <thead>
      <tr v-if="headers">
        <th v-for="(val,key) in headers" :colspan="val['colspan']" :rowspan="isHeader(key)">
            <center>{{ val['caption'] }}</center>
        </th>
      </tr>
      <tr>
        <th v-if="!headers">
            <input
                type="checkbox"
                v-model="allSelected"
                @change="selectAll"
                :disabled="headers">
        </th>
        <template v-if="!headers">
            <th v-for="key in additionalColumns"
              @click="sortBy(key)"
              :class="{ active: sortKey == key }">
                <center>
                    {{ columnName(key) }}
                  <span class="arrow" :class="sortOrders[key] > 0 ? 'asc' : 'dsc'" v-if="!tagUrl">
                  </span>
                </center>
            </th>
        </template>
        <template v-if="headers">
            <th v-for="key in additionalColumns">
                <center>{{ columnName(key) }}</center>
            </th>
        </template>
        <th v-if="!readonly"></th>
        <th v-if="childComponent">
            <center v-if="childComponent['type'] == 'checkbox'">
                {{ capitalize(childComponent['instance']) }}
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
                {{ capitalize(childComponent['instance']) }}
            </span>
        </th>
      </tr>
    </tfoot>
    <tbody>
      <tr v-for="entry in filteredData" :class="{ 'table-active': checked[entry['id']] }">
        <td>
            <input
                type="checkbox"
                v-model="checked[entry['id']]"
                :true-value="entry['id']"
                @change="checkedRow" :disabled="isCheckbox(entry['checkbox'])">
        </td>
        <td v-for="(key,index) in columns">
            <a v-if="recursive == true" @click="getChildren(entry['id'])">{{ entry[key] }}</a>
            <span v-if="recursive == false">{{ entry[key] }}</span>
            <span v-if="entry['tag'] && entry['tag']['field']==key" class="badge" :class="entry['tag']['color']">{{ entry['tag']['caption'] }}</span>
            <template v-if="isTaggedData">
                <span v-for="(tags,dataset) in taggedData">
                    <span class="d-flex flex-wrap gap-1">
                        <template v-if="tagUrl==dataset">
                            <span v-for="tag in tags">
                                <gov2tagged :source_id="parseInt(entry['id'])" :tagged-data="tag" v-if="tag[instance+'_id'] == entry['id'] && key == dataset" :tag-closeable="tagCloseable" :tag-caption="tagCaption"></gov2tagged>
                            </span>
                        </template>
                        <template v-if="tagUrl!=dataset">
                            <span v-for="tag in tags">
                                <gov2tagged :source_id="parseInt(entry['id'])" :tagged-data="tag" v-if="tag[instance+'_id'] == entry['id'] && key == dataset" :tag-caption="tagCaption"></gov2tagged>
                            </span>
                        </template>
                    </span>
                </span>
            </template>
            &nbsp;
        </td>
        <td v-if="!readonly">
            <a class="badge bg-warning text-dark" @click="edit(entry['id'])">Edit</a>
            <a class="badge bg-danger" @click="del(entry['id'])" v-if="entry['children'] == 0 || !entry['children']">Del</a>
            <a class="badge bg-warning text-dark" @click="hasChildren(entry['children'])" v-if="entry['children'] > 0">Del</a>
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
// Port Vue 3 dari apps/components/vue/gov2table.vue (#6118 3b).
// Kontrak event & perilaku dipertahankan 1:1; perubahan sadar:
// - filters.capitalize → method (filters dihapus di Vue 3)
// - anak gov2tagging/tagged/checkbox/progress di-bundle (bukan httpVueLoader)
// - push kolom tag ke columns dilakukan sekali di created (dulu di computed,
//   yang memutasi prop — pola yang memicu recursive update di Vue 3)
import eventBus from '../eventBus.js'
import Gov2Tagging from './Gov2Tagging.vue'
import Gov2Tagged from './Gov2Tagged.vue'
import Gov2Checkbox from './Gov2Checkbox.vue'
import Gov2Progress from './Gov2Progress.vue'

export default {
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
    instanceTo: {
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
    'gov2tagging': Gov2Tagging,
    'gov2tagged': Gov2Tagged,
    'gov2checkbox': Gov2Checkbox,
    'gov2progress': Gov2Progress,
  },
  data () {
    var sortOrders = {}
    this.columns.forEach(function (key) {
        sortOrders[key] = 1
    });
    return {
        gridData: [],
        records: 0,
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
        allSelected: false,
    }
  },
  computed: {
    additionalColumns: function() {
        return this.columns;
    },
    isTaggedData: function () {
        if (Object.keys(this.taggedData).length > 0) {return true;}
        return false;
    },
    filteredData: function () {
        var currentPage = this.currentPage || '1'
        var sortKey = this.sortKey
        var filterKey = this.filterKey && this.filterKey.toLowerCase()
        var order = this.sortOrders[sortKey] || 1
        var data = this.gridData
        data = this.paging(data);

      if (filterKey) {
        data = data.filter(function (row) {
          return Object.keys(row).some(function (key) {
            return String(row[key]).toLowerCase().indexOf(filterKey) > -1
          })
        })
        data = this.paging(data);
        this.pagination(data.length);
        this.gotoPage(1);
        eventBus.$emit('setCurrentPage', 1);
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
        data = this.paging(data);
      }
      if (currentPage) {
        data = data.filter(function (row) {
          return Object.keys(row).some(function (key) {
            return String(row['page']) == currentPage
          })
        })
      }
      return data
    }
  },
    mounted() {
        this.$nextTick(function() {
            window.addEventListener('resize', this.setTableWidth);
        })
    },
    unmounted() {
        window.removeEventListener('resize', this.setTableWidth);
    },
  methods: {
    capitalize: function (str) {
        if (str) {
            return str.charAt(0).toUpperCase() + str.slice(1)
        }
    },
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
        if (this.allSelected == true) {
            this.filteredData.forEach(function (row) {
                if (row.checkbox) {
                    selected[row.id] = row.id;
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
        var total = 0;
        this.filteredData.forEach(function (row) {
            total = parseInt(total) + parseInt(row[key]);
        });
        if (!isNaN(total)) {
            eventBus.$emit('total' + key, total);
            return total;
        } else {
            eventBus.$emit('total' + key, this.filteredData.length);
            return this.filteredData.length;
        }
    },
    isHeader: function(data) {
        if (this.headers && data == 0) {
            return 2;
        } else {
            return 1;
        }
    },
    checkedRow: function () {
        eventBus.$emit('setChecked', this.checked);
    },
    resetChecked: function () {
        this.checked = [];
        this.checkedRow();
    },
    reload: function() {
        location.reload();
    },
    loadTags: function(data) {
        this.tags = Array.from(Object.keys(data), k => data[k]);
    },
    getTags: function (tagUrl) {
        let url;
        if (this.childComponent['parent_id']) {
            url = tagUrl + '/table/1/' + this.childComponent['parent_id'];
        } else {
            url = tagUrl + '/table/1/-1';
        }
        axios.get(url)
            .then(response => this.loadTags(response.data))
            .catch(error => eventBus.$emit('openNotif', error.response.data));
    },
    columnName: function (data) {
        let newColName = data;
        if (this.dynColName && this.gridData.length > 0 && this.gridData[0] != 'empty') {
            if (data == this.dynColName['col1']) {
                newColName = this.gridData[0][this.dynColName['col2']];
                if (newColName == "") {
                    newColName = data;
                } else {
                    newColName = newColName.charAt(0).toUpperCase() + newColName.slice(1);
                }
            } else {
                newColName = data.charAt(0).toUpperCase() + data.slice(1);
            }
        } else {
            newColName = data.charAt(0).toUpperCase() + data.slice(1);
        }
        return newColName;
    },
    showTagging: function (level, source_id) {
        var isShow = true;
        if (this.childComponent) {
            if (this.showTaggingAtLevel) {
                if (this.showTaggingAtLevel == level) {
                    isShow = true;
                } else {
                    isShow = false;
                }
            } else if (this.tagsUrl) {
                for (let row in this.tagsUrl) {
                    for (let tags in this.taggedData[this.tagsUrl[row]]) {
                        if (this.taggedData[this.tagsUrl[row]][tags]['source_id'] === source_id) {
                            if (this.taggingId) {
                                if (this.taggingId == this.taggedData[this.tagsUrl[row]][tags]['target_id']) {
                                    isShow = true;
                                    break;
                                } else {
                                    isShow = false;
                                }
                            } else {
                                isShow = true;
                                break;
                            }
                        } else {
                            isShow = false;
                        }
                    }
                }
            } else {
                isShow = true;
            }
        } else {
            isShow = false;
        }
        return isShow;
    },
    getTaggedData: function (otherTag) {
        var config = {
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        }
        let url;
        if (otherTag) {
            url = otherTag + '/getTags/' + this.parent;
        } else {
            url = this.tagUrl + '/getTags/' + this.parent;
        }
        axios.get(url, config)
            .then(response => this.loadTaggedData(response.data, otherTag))
            .catch(error => this.onGetDataFail(error.response.data));
    },
    loadTaggedData: function(data, otherTag) {
        var _tags = Array.from(Object.keys(data), k => data[k]);
        var _tagUrl = this.tagUrl;

        if (otherTag) {
            this.taggedData = {};
            this.taggedData[otherTag] = _tags;
        } else {
            if (this.taggedData[_tagUrl]) {
                this.taggedData[_tagUrl] = {}
                this.taggedData[_tagUrl] = Object.assign(this.taggedData[_tagUrl], _tags);
                var taggedData2 = this.taggedData;
                this.taggedData = {};
                this.taggedData = taggedData2;
            } else {
                this.taggedData = {};
                this.taggedData[_tagUrl] = _tags;
            }
        }
    },
    scrolling: function (data) {
        this.setScroll(data);
        this.getData();
        this.gotoPage(1);
        eventBus.$emit('setCurrentPage', 1);
    },
    setParent: function (data) {
        if (data) {
            this.parent = data;
            eventBus.$emit('parent' + this.instance, data);
        }
    },
    setScroll: function (data) {
        this.scroll = parseInt(data);
        eventBus.$emit('pageScroll' + this.instance, this.scroll);
    },
    paging: function (data) {
        var page = 1;
        var number = 1;
        for (let field in data) {
            data[field]['page'] = page;
            var mod = number % this.itemPerPage;
            if (mod == 0) { page++; }
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
        if (data['data'] == 'empty') {
            // Respons kosong: jangan isi gridData dgn string junk — paging()
            // menulis row['page'], di ESM strict mode assignment ke string
            // primitif = TypeError yang meledakkan render seluruh tabel
            this.gridData = [];
            this.records = 0;
        } else {
            this.gridData = Array.from(Object.keys(data), k => data[k]);
            this.records = this.gridData.length;
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
        eventBus.$emit('loadingDone', this.instance);
    },
    pagination: function (records) {
        var pagination = [];
        this.records = records;
        pagination['records'] = records;
        pagination['itemPerPage'] = this.itemPerPage;
        eventBus.$emit('pagination', pagination);
        eventBus.$emit('setRows' + this.instance, this.records);
    },
    edit: function (id) {
        axios.get(this.getUrl + '/edit/' + parseInt(id))
            .then(response => eventBus.$emit('dataEdit', response.data))
            .catch(error => this.onGetDataFail(error.response.data));
    },
    del: function (id) {
        eventBus.$emit('dataDel', parseInt(id));
    },
    hasChildren: function (data) {
        eventBus.$emit('hasChildren', parseInt(data));
    },
    sayUrl: function (url, listener) {
        let printUrl;
        let linger = url.indexOf('-1');
        let reset = url.indexOf('-2');
        if (linger > 0 || reset > 0) {
            printUrl = "Invalid";
        } else {
            printUrl = url;
        }
        if (listener) {
            eventBus.$emit('printUrl' + listener, printUrl);
        } else {
            eventBus.$emit('printUrl' + this.instance, printUrl);
        }
    },
    getData: function(id) {
        eventBus.$emit('loadingStart', this.instance);
        this.setParent(id);
        let url;
        if (this.parent) { url = this.getUrl + '/table/' + this.scroll + '/' + this.parent; }
        else if (this.recursive) { url = this.getUrl + '/table/' + this.scroll + '/0'; }
        else { url = this.getUrl + '/table/' + this.scroll; }
        this.sayUrl(url);
        axios.get(url)
            .then(response => this.loadData(response.data))
            .catch(error => this.onGetDataFail(error.response.data));
    },
    getTotalRecord: function(id) {
        let url;
        if (this.recursive) { url = this.getUrl + '/count/' + id; }
        else { url = this.getUrl + '/count'; }
        this.sayUrl(url, 'count');
        axios.get(url)
            .then(response => this.onGetTotalRecordDone(response.data))
            .catch(error => this.onGetDataFail(error.response.data));
    },
    onGetTotalRecordDone: function(data) {
        eventBus.$emit('setTotalRecord' + this.instance, data);
        eventBus.$emit('loadingDone', this.instance);
    },
    getChildren: function (id) {
        this.setScroll(1);
        this.getData(id);

        eventBus.$emit('refreshPath' + this.instance, id);
        axios.get(this.getUrl + '/children/' + id)
            .then(response => eventBus.$emit('setLevel', response.data['level']))
            .catch(error => this.onGetDataFail(error.response.data));
        this.gotoPage(1);
        eventBus.$emit('setCurrentPage', 1);
    },
    onGetDataFail: function(data) {
        console.log(data);
        this.gridData = [];
        eventBus.$emit('loadingDone', this.instance);
        eventBus.$emit('openNotif', data);
    },
    setInstance: function() {
        eventBus.$on('getChildren' + this.instance, this.getChildren);
    },
    setTableWidth: function () {
        this.tableWidth = document.documentElement.clientWidth - 220;
    }
  },
  created: function () {
    // Kolom tambahan (tag/tagsUrl) di-push SEKALI di sini — di Vue 2 push
    // terjadi di computed additionalColumns (mutasi prop); net effect sama.
    if (this.tagUrl && this.childComponent && this.childComponent['type'] == 'dropdown') {
        this.columns.push(this.tagUrl);
    }
    if (this.tagsUrl) {
        for (let row in this.tagsUrl) {
            this.columns.push(this.tagsUrl[row]);
        }
    }

    if (this.recursive) {
        if (this.selected) {
            this.getData(this.selected);
        } else {
            this.getData(-1);
        }
    } else {
        this.getData();
    }
    eventBus.$emit('refreshPath' + this.instance, -1);
    eventBus.$on('changepage', this.gotoPage);
    eventBus.$on('formFail', this.errorMessage);
    eventBus.$on('refreshData' + this.instance, this.getData);
    eventBus.$on('setInstance', this.setInstance);
    eventBus.$on('getChildren' + this.instance, this.getChildren);
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
    eventBus.$on('refreshTags' + this.instance, this.getTaggedData);
    eventBus.$on('reload', this.reload);
    eventBus.$on('resetChecked', this.resetChecked);
    this.setTableWidth();
  }
}
</script>

<style>

/* Cube theme table wrapper */
.table-container-outer {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e5e6ec;
}

.table-container {
  overflow-x: auto;
  margin: 0;
  width: 100%;
}

.table-container::-webkit-scrollbar {
  -webkit-appearance: none;
  width: 14px;
  height: 14px;
}

.table-container::-webkit-scrollbar-thumb {
  border-radius: 8px;
  border: 3px solid #fff;
  background-color: rgba(0, 0, 0, .3);
}

.table-container-fade {
  position: absolute;
  right: 0;
  width: 20px;
  height: 100%;
  background: linear-gradient(90deg, rgba(255,255,255,0), #fff);
  z-index: 1;
  pointer-events: none;
}

/* Table styling */
.table-container .table {
  margin-bottom: 0;
  font-size: 0.9rem;
}

.table-container .table thead th {
  background-color: #f6f7fb;
  border-bottom: 2px solid #e5e6ec;
  font-weight: 600;
  color: #333;
  text-align: center;
  vertical-align: middle;
  white-space: nowrap;
  padding: 0.6rem 0.75rem;
}

.table-container .table tbody td {
  color: #616161;
  vertical-align: middle;
  padding: 0.5rem 0.75rem;
}

.table-container .table tfoot th {
  background-color: #f6f7fb;
  border-top: 2px solid #e5e6ec;
  font-weight: 600;
  text-align: center;
  padding: 0.6rem 0.75rem;
}

.table-container .table tr:last-child td {
  border-bottom: 0;
}

/* Sort indicator */
th.active {
  color: #5b4fb9;
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
  border-bottom: 4px solid #5b4fb9;
}

.arrow.dsc {
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 4px solid #5b4fb9;
}

</style>
