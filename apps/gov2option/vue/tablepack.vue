<template>
    <div class="main-box" id="mb" v-if="isActive">
        <div class="main-box-body">
            <div class="block no-bottom">
                <div class="block-header">
                    <div class="pull-left">
                        <drillup
                            v-if="drillup"
                            :is-active="true"
                            :is-horizontal="true"
                            path-url="proyek/breadcrumb"
                        ></drillup>
                        <div class="block-title">
                            {{ title ? title : instance.split('_').join(' ').toUpperCase()}} {{ myBreadcrumb }}
                        </div>
                        
                    </div>
                    <div class="pull-right filter-block">
                        <b-button @click="refresh"
                                  v-show="!refreshState"
                                  variant="primary"
                                  v-b-tooltip.hover
                                  title="Bersihkan pencarian">
                                <span class="icon">
                                  <i class="fa fa-refresh"></i>
                                </span>
                        </b-button>
                        <gov2search-bs4 :instance="instance" :is-active="isActive && isSearch"></gov2search-bs4>
                        <gov2button-bs4 button-label="Tambah Data!" v-show="!readonly && permission.canAdd" :instance="instance"></gov2button-bs4>
                        <button disabled class="btn btn-primary" v-show="!readonly && isBulkApproval"><i class="fa fa-check"></i> Bulk Approval</button>
                    </div>
                </div>
            </div>

            <div class="ren-table table-responsive" :style="{ maxWidth:myWidth+'px' }">
                <table class="table table-bordered table-hover">
                    <thead style="text-align: center; background: #efefef;" >
                    <tr v-if="headers">
                        <th v-for="(val,key) in headers" 
                            :colspan="val['colspan']"  
                            :rowspan="val['rowspan']">
                            <center>{{ val['caption'] }}</center>
                        </th>
                        <th v-if="!readonly || isRowHistory"></th>
                    </tr>
                    <tr v-if="headers2">
                        <th v-for="(val,key) in headers2" 
                            :colspan="val['colspan']"  
                            :rowspan="val['rowspan']">
                            <center>{{ val['caption'] }}</center>
                        </th>
                        <th v-if="!readonly || isRowHistory"></th>
                    </tr>
                    <tr>
                        <th style="vertical-align: middle;">
                            <b-form-checkbox
                                    v-model="allSelected"
                                    :disabled="headers && headers.length > 0">
                            </b-form-checkbox>
                        </th>
                        <th v-for="key in additionalColumns"
                            @click="sortBy(key)"
                            :class="{ active: sortKey == key }" v-if="!headers && !headers2">{{ columnName(key) }}
                            <span :class="sortOrders[key] > 0 ? 'fa fa-caret-up' : 'fa fa-caret-down'" v-if="!tagUrl"></span>
                        </th>
                        <th v-for="key in additionalColumns" v-if="headers || headers2">
                            <center>{{ columnName(key) }}</center>
                        </th>
                        <th v-if="!readonly || isRowHistory"></th>
                        <th v-if="childComponent">
                        <span v-if="childComponent['type'] == 'checkbox'">
                            {{ childComponent['instance'] | capitalize}}
                        </span>
                        </th>
                        <th v-if="isSetactive"> Aksi </th>
                    </tr>
                    </thead>
                    
                    <tbody v-for="entry in filteredData" :class="{ 'is-selected': checked[entry['id']] }" v-bind:key="entry.id">
                    <tr>

                        <td style="text-align:center">
                            <b-form-checkbox
                                    v-model="checked[entry['id']]"
                                    :value="entry['id']"
                                    @change="checkedRow(entry['id'], entry)"
                                    :disabled="isCheckbox(entry['checkbox']) && !selectable">
                            </b-form-checkbox>
                        </td>

                        <td v-for="(key, i) in columns"
                            :class="{'text-center': key === 'status' || key === 'kode' || key === 'level_label' || key === 'id','first-column': isCollapsibleRow && i === 0}">

                            <i class="text-primary" :class="{
                                'fa fa-minus-circle': expandedRows.includes(entry.id),
                                'fa fa-plus-circle': !expandedRows.includes(entry.id)}"
                                v-if="isCollapsibleRow && i === 0" @click="toggleRow(entry.id)"
                                :data-test="`button-collapsible-${entry.id}`"></i>

                            <div v-if="entry['level_label']==levelEnd">
                                <span v-if="recursive && (entry['level_label']==levelEnd) && (key!=='status')">{{ entry[key] }}</span>
                            </div>
                            <div v-else>
                                <a v-if="recursive && (key === 'nama' || clickableCol.includes(key))"
                                   @click="getChildren(entry['id'])"
                                   :class="{'cp text-info': key ===  'nama' || clickableCol.includes(key)}"
                                   :id="`recursive-link-${entry.id}`"
                                   :data-test="`recursive-link-${entry.id}`">{{ entry[key] }} </a>
                                <a v-if="recursive == true && key !== 'status' && key !== 'nama'
                               && !clickableCol.includes(key)" :class="{'cp text-info': key ===  'nama'}">{{ entry[key] }} </a>
                                <span v-if="recursive == false && key !== 'status'" >{{ entry[key] }}</span>
                                <b-tooltip :target="`recursive-link-${entry.id}`" triggers="hover" v-if="childrenTooltip">
                                    Data ini memiliki {{entry['children']}} child(s)
                                </b-tooltip>
                            </div>
                            <!-- if row key == tag -->
                            <b-badge variant="primary"
                                     v-if="entry['tag'] && entry['tag']['field']==key"
                                     :variant="entry['tag']['color']">
                                {{ entry['tag']['caption'] }}
                            </b-badge>

                            <span v-for="(tags,dataset) in taggedData" v-if="isTaggedData">
                            <div class="tags">
                                <span v-for="tag in tags" v-if="tagUrl==dataset">
                                    <bs4tagged
                                            v-if="tag.source_id == entry.id && key == dataset"
                                            :source_id="parseInt(entry['id'])"
                                            :tagged-data="tag"
                                            :tag-closeable="tagCloseable"
                                            v-bind:key="tag.target_id"
                                            :tag-caption="tagCaption">
                                    </bs4tagged>
                                </span>
                                <span v-for="tag in tags" v-if="tagUrl!=dataset">
                                    <bs4tagged :source_id="parseInt(entry['id'])"
                                               :tagged-data="tag"
                                               v-if="tag['source_id'] == entry['id'] && key == dataset"
                                               :tag-caption="tagCaption">
                                    </bs4tagged>
                                </span>
                            </div>
                            </span>

                            <gov2badges-bs4 :row="entry" :row-key="key"></gov2badges-bs4>
                        </td>
                        <td style="text-align:center" v-if="instance === 'program'"
                            v-show="showColumnPenelaah">
                            <submitapproval :row="entry" :post-url="postUrl"
                                            :readonly="readonly"></submitapproval>
                        </td>
                        <td style="text-align:center" v-if="instance === 'program' || 
                            instance === 'kegiatan' || 
                            instance === 'sasaran_program' ||
                            instance === 'sasaran_kegiatan'"
                            v-show="showColumnPenelaah">
                            <inputapproval 
                                :row="entry" :post-url="postUrl"
                                :readonly="readonly"
                            ></inputapproval>
                        </td>

                        <td v-if="!readonly" style="text-align:center" >
                            <b-dropdown 
                                id="dropdown-right" right  variant="primary" size="sm"
                                :data-test="`button-action-${entry.id}`">
                                <template slot="button-content">
                                    <i class="fa fa-cog"></i>
                                </template>
                                <b-dropdown-item href="#" @click="edit(entry['id'])" :disabled="!permission.canEdit"><i class="fa fa-edit" ></i> Ubah</b-dropdown-item>
                                <b-dropdown-item href="#" @click="del(entry['id'])" v-if="entry['children'] == 0 || !entry['children']" :disabled="!permission.canDelete"><i class="fa fa-trash"></i> Del</b-dropdown-item>
                                <b-dropdown-item href="#" @click="hasChildren(entry['children'])" v-if="entry['children'] > 0" :disabled="!permission.canDelete"><i class="fa fa-trash"></i> Hapus</b-dropdown-item>
                                <b-dropdown-divider v-if="isRowHistory || isRowResetPassword"></b-dropdown-divider>
                                <b-dropdown-item href="#" v-if="isRowHistory" @click="getRowHistory(entry['id'])"><i class="fa fa-calendar"></i> History</b-dropdown-item>
                                <b-dropdown-item href="#" v-if="isRowResetPassword" @click="toggleResetPassword(entry)"><i class="fa fa-calendar"></i>Reset Password</b-dropdown-item>
                            </b-dropdown>
                        </td>
                        <td v-if="isRowHistory && readonly" style="text-align:center">
                            <b-dropdown right variant="primary" size="sm">
                                <template slot="button-content">
                                    <i class="fa fa-cog"></i>
                                </template>
                                <b-dropdown-item href="#" @click="getRowHistory(entry['id'])"><i class="fa fa-calendar"></i> History</b-dropdown-item>
                            </b-dropdown>
                        </td>
                        <td v-if="isSetactive">
                            <setactive :id="entry['id']" :status="entry['status']" :post-url="postUrl" :instance="instance"></setactive>
                        </td>
                        <td v-if="childComponent && childComponent['type'] == 'dropdown'">
                            <bs4tagging
                                    :post-url="postUrl"
                                    :get-url="childComponent['instance']"
                                    :source_id="parseInt(entry['id'])"
                                    :instance="instance"
                                    v-if="showTagging(entry['level'],entry['id'])"
                                    :tag-limit="tagLimit"
                                    :tags="tags"
                                    v-bind:key="entry.id"
                                    :tag-when="tagWhen"
                                    :tag-caption="tagCaption"></bs4tagging>
                        </td>
                        <td v-if="childComponent && childComponent['type'] == 'checkbox'">
                            <rencheckbox :post-url="postUrl" :source_id="parseInt(entry['id'])" :instance="instance" v-if="showTagging(entry['level'],entry['id'])" :tags="tags" :tag-url="tagUrl" :parent_id="parent" :tag-limit="tagLimit"></rencheckbox>
                        </td>
                    </tr>
                    <tr v-show="expandedRows.includes(entry.id) && isCollapsibleRow" class="row-detail">
                        <td :colspan="columns.length+2">
                            <b-spinner variant="info" label="Memuat..." v-if="loading && !rowDetails[`i${entry.id}`]"></b-spinner>
                            <fisikcard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'fisik' || entry['level_label'] === 'instance_fisik')"></fisikcard>
                            <keuangancard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'keuangan' || entry['level_label'] === 'instance_keuangan')"></keuangancard>
                            <profilcard :profil="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'profil' || entry['level_label'] === 'instance_profil')"></profilcard>
                            <pelaksanacard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'pelaksana' || entry['level_label'] === 'instance_pelaksana')"></pelaksanacard>
                            <kategoricard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'kategori' || entry['level_label'] === 'instance_kategori')"></kategoricard>
                            <paketcard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'paket' || entry['level_label'] === 'instance_paket')"></paketcard>
                            <disbursementcard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'disbursement' || entry['level_label'] === 'instance_disbursement')"></disbursementcard>
                            <petugascard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'petugas' || entry['level_label'] === 'instance_petugas')"></petugascard>
                            <masalahcard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'masalah' || entry['level_label'] === 'instance_masalah')"></masalahcard>
                            <anggarancard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'anggaran' || entry['level_label'] === 'instance_anggaran')"></anggarancard>
                            <penyerapancard :row="rowDetails[`i${entry.id}`]" v-if="!loading && (entry['level_label'] === 'penyerapan' || entry['level_label'] === 'instance_penyerapan')"></penyerapancard>
                            <dpcard :row="entry" v-if="collapsibleLocal && getUrl==='aktif'"></dpcard>
                            <sessioncard :row="entry" v-if="collapsibleLocal && getUrl==='sessions'"></sessioncard>
                            <accountcard :row="entry" v-if="collapsibleLocal && getUrl==='accounts'"></accountcard>
                        </td>
                    </tr>
                    </tbody>
                    <tbody v-if="filteredData.length === 0">
                        <td style="text-align:center">
                            <b-form-checkbox :disabled="true">
                            </b-form-checkbox>
                        </td>
                        <td :colspan="columns.length" class="text-center">No data found.</td>
                        <td v-if="!readonly || childComponent"></td>
                    </tbody>
                    <!-- tfoot -->
                    <tfoot v-if="!isTotal" style="text-align: center; background: #efefef; text-transform: uppercase; font-size: 14px;">
                        <tr>
                            <th rowspan="2" style="vertical-align: middle;">
                                <b-form-checkbox
                                        v-model="allSelected"
                                        :disabled="headers && headers.length > 0">
                                </b-form-checkbox>
                            </th>
                            <th rowspan="2" style="vertical-align: middle;" v-for="key in additionalColumns"
                                @click="sortBy(key)"
                                :class="{ active: sortKey == key }">{{ columnName(key) }}
                                <span :class="sortOrders[key] > 0 ? 'fa fa-caret-up' : 'fa fa-caret-down'" v-if="!tagUrl"></span>
                            </th>
                            <th rowspan="2" v-if="!readonly || isRowHistory"></th>
                            <th rowspan="2" v-if="childComponent">
                                <span v-if="childComponent['type'] == 'checkbox'">
                                    {{ childComponent['instance'] | capitalize}}
                                </span>
                            </th>
                            <th v-if="isSetactive"> Aksi </th>
                        </tr>
                    </tfoot>

                    <tfoot v-if="isTotal">
                        <tr>
                            <th></th>
                            <th v-for="key in isTotal">
                                {{ countTotal(key) }}
                            </th>
                        </tr>
                    </tfoot>
                    <!-- tfoot end-->
                </table>
            </div>

            <div class="row"></div>

            <b-row>
                <b-col :cols="itemPerPageCols">
                    <gov2itemperpage-bs4 :interval="interval" :instance="instance" v-if="isItemPerPage"></gov2itemperpage-bs4>
                </b-col>
                <b-col :cols="scrollCols">
                    <gov2scroll-bs4 :instance="instance" :is-active="isActive"></gov2scroll-bs4>
                </b-col>
                <b-col v-if ="(12 - (itemPerPageCols+scrollCols+paginationCols)) > 0"
                       :cols="12 - (itemPerPageCols+scrollCols+paginationCols)">
                </b-col>
                <b-col :cols="paginationCols">
                    <gov2pagination-bs4
                        :is-active="isActive"
                        :records="records"
                        :scroll-interval="scrollInterval"
                        :item-per-page="itemPerPage"
                        :instance="instance"
                        v-if="isPagination">
                    </gov2pagination-bs4>
                </b-col>
            </b-row>
            <b-row>&nbsp;</b-row>
        </div>
        <gov2resetpassword v-if="isRowResetPassword" :post-url="postUrl"></gov2resetpassword>
    </div>
</template>

<script>
    module.exports = {
        name: 'tablepack',
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
            levelEnd: {
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
            headers2: {},
            isBordered: Boolean,
            isTotal: Array,
            wideTable: Boolean,
            isCollapsibleRow: Boolean,
            interval: Array,
            scrollInterval: Number,
            showColumnPenelaah: {
                type: Boolean,
                default: true
            },
            isBulkApproval: {
                type: Boolean,
                default: false
            },
            drillup: Boolean,
            isSetactive: {
                type: Boolean,
                default: false
            },
            title: String,
            crudPermission: {
                type: Boolean,
                default: false
            },
            customColumnNames: {
                type: Object,
                default: null
            },
            isItemPerPage: {
                type: Boolean,
                default: true
            },
            isPagination: {
                type: Boolean,
                default: true
            },
            isSearch: {
              type: Boolean,
              default: true
            },
            clickableCol: {
                type: Array,
                default: function () {
                    return [];
                }
            },
            selectable: {
                type: Boolean,
                default: false
            },
            tagWhen: {
                type: Array,
                default: function () {
                    return []
                }
            },
            isRowHistory: {
                type: Boolean,
                default: false
            },
            collapsibleLocal: {
                type: Boolean,
                default: false
            },
            readLastUrlParam: {
                type: Boolean,
                default: false
            },
            addBreadcrumb: {
                type: Boolean,
                default: false
            },
            longUrl: String,
            isRowResetPassword: {
                type: Boolean,
                default: false
            },
            itemPerPageCols: {
                type: Number,
                default: 2
            },
            scrollCols: {
                type: Number,
                default: 2
            },
            paginationCols: {
                type: Number,
                default: 4
            },
            childrenTooltip: {
                type: Boolean,
                default: true
            }
        },
        components: {
            'bs4tagging': httpVueLoader('./_bs4tagging.vue'),
            'bs4tagged': httpVueLoader('./_bs4tagged.vue'),
            'rencheckbox': httpVueLoader('rencheckbox.vue'),
            'gov2progress': httpVueLoader('./_gov2progress.vue'),
            'gov2component': httpVueLoader('./gov2component.vue'),
            'gov2search-bs4': httpVueLoader('./gov2search-bs4.vue'),
            'gov2button-bs4': httpVueLoader('./gov2button-bs4.vue'),
            'gov2pagination-bs4': httpVueLoader('./gov2pagination-bs4.vue'),
            'gov2itemperpage-bs4': httpVueLoader('./gov2itemperpage-bs4.vue'),
            'gov2scroll-bs4': httpVueLoader('./gov2scroll-bs4.vue'),
            'gov2badges-bs4': httpVueLoader('./gov2badges-bs4.vue'),
            'drillup': httpVueLoader('./drillup.vue'),
            'inputapproval': httpVueLoader('./inputapproval.vue'),
            'submitapproval': httpVueLoader('./submitapproval.vue'),
            'setactive': httpVueLoader('./setactive.vue'),
            'keuangancard': httpVueLoader('./keuangancard.vue'),
            'fisikcard': httpVueLoader('./fisikcard.vue'),
            'profilcard': httpVueLoader('./profilcard.vue'),
            'pelaksanacard': httpVueLoader('./pelaksanacard.vue'),
            'kategoricard': httpVueLoader('./kategoricard.vue'),
            'paketcard': httpVueLoader('./paketcard.vue'),
            'disbursementcard': httpVueLoader('./disbursementcard.vue'),
            'petugascard': httpVueLoader('./petugascard.vue'),
            'masalahcard': httpVueLoader('./masalahcard.vue'),
            'anggarancard': httpVueLoader('./anggarancard.vue'),
            'penyerapancard': httpVueLoader('./penyerapancard.vue'),
            'dpcard': httpVueLoader('./dpcard.vue'),
            'sessioncard': httpVueLoader('./sessioncard.vue'),
            'accountcard': httpVueLoader('./accountcard.vue'),
            'gov2resetpassword': httpVueLoader('./gov2resetpassword.vue'),
        },
        data () {
            var sortOrders = {};
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
                allSelected: false,
                expandedRows: [],
                rowDetails: {},
                loading: true,
                pendingRequest: {},
                getDataCounter: 0,
                permission: {
                    canAdd: true,
                    canEdit: true,
                    canDelete: true,
                },
                myWidth: 1000,
                toggle: false,
                refreshState: true,
                myBreadcrumb: '',
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
                let res = str;
                if (str) {
                    try {
                        res = str.charAt(0).toUpperCase() + str.slice(1)
                    } catch (e) {
                        res = str
                    }
                }
                return res;
            }
        },
        mounted() {
            // this.$nextTick(function() {
            //     window.addEventListener('resize', this.setTableWidth);
            // });
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
                if (!isNaN(total)) {
                    eventBus.$emit('total'+key,total);
                    return total;
                } else {
                    eventBus.$emit('total'+key,this.filteredData.length);
                    return this.filteredData.length;
                }
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
            checkedRow: function (id=null, row=null) {
                if (id) {
                    if (this.checked.hasOwnProperty(id)) {
                        if (this.checked[id]) {
                            this.checked[id] = null
                        } else {
                            this.checked[id] = id
                        }
                    } else {
                        this.checked[id] = id
                    }
                }
                eventBus.$emit('setChecked',this.checked);
                eventBus.$emit('setCheckedRow', row);
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
                        if (newColName=="" || typeof newColName === 'undefined') {
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
                if (this.customColumnNames != null){
                    if (this.customColumnNames.hasOwnProperty(data))
                        {
                            newColName = this.customColumnNames[data];
                        }
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
                    this.sortKey = key;
                    this.sortOrders[key] = this.sortOrders[key] * -1
                }
            },
            gotoPage: function (page) {
                this.currentPage = parseInt(page);
                // console.log("table, pagination instance of " + this.instance)
            },
            errorMessage: function (errors) {
                console.log(errors);
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
                    .then(response => {
                        eventBus.$emit('dataEdit',response.data);
                        if (this.instance !== "")
                        {
                            eventBus.$emit('onInstance', this.instance);
                        }
                    })
                    .catch(error => this.onGetDataFail(error.response.data));
            },
            del: function (id) {
                eventBus.$emit('onInstance', this.instance);
                eventBus.$emit('dataDel',parseInt(id));
            },
            hasChildren: function (data) {
                eventBus.$emit('hasChildren',parseInt(data));
            },
            sayUrl: function (url,listener) {
                let printUrl;
                let linger=url.indexOf('-1');
                let reset=url.indexOf('-2');
                if ( linger>0 || reset>0 ) {
                    printUrl="Invalid";
                } else {
                    // printUrl=url.replace(this.instance+'/', "");
                    printUrl=url;
                    // printUrl=url.replace(this.getUrl+'/', "");
                }
                if (listener) {
                    eventBus.$emit('printUrl'+listener,printUrl);
                } else {
                    eventBus.$emit('printUrl'+this.instance,printUrl);
                }
            },
            getData: function(id) {
                // const CancelToken = axios.CancelToken;
                eventBus.$emit('loadingStart',this.instance);
                this.setParent(id);
                // console.log(id);
                
                if (this.parent) {url=this.getUrl+'/table/'+this.scroll+'/'+this.parent;}
                else if (this.recursive) {url=this.getUrl+'/table/'+this.scroll+'/0';}
                else {url=this.getUrl+'/table/'+this.scroll+'/';}

                if (this.readLastUrlParam) {
                    let currUrl = window.location.href;
                    currUrl = currUrl.split('/')

                    const last_param = currUrl[currUrl.length-1];
                    url = `${url}?last_param=${last_param}`;
                }
                
                this.sayUrl(url);
                // const requestName = `getData${this.instance}`;

                // if (!this.pendingRequest.hasOwnProperty(requestName))
                // {
                //     axios.get(url, {cancelToken: new CancelToken(c => {
                //             this.pendingRequest[requestName] = c;
                //         })})
                //         .then(response => {
                //             this.loadData(response.data);
                //         })
                //         .catch(error => {
                //             // this time we still do nothing
                //         });
                // } else {
                //     if (this.getDataCounter > 1)
                //     {
                //         this.pendingRequest[requestName]();
                //     }
                // }

                // console.log(url)
                axios.get(url)
                    .then(response => {
                        this.loadData(response.data);
                    })
                    .catch(error => {
                        // this time we still do nothing
                    });
            },
            loadData: function(data) {
                // console.log(data);
                this.gridData=Array.from(Object.keys(data), k=>data[k]);

                if (data['data'] === 'empty') {
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
                if (document.getElementById("mb")) {
                    let ww = document.getElementById("mb").offsetWidth;
                    this.myWidth = ww-60;
                }
                // const requestName = `getData${this.instance}`;
                // delete this.pendingRequest[requestName];
                // this.getDataCounter++;
            },
            getTotalRecord: function(id) {
                if (this.recursive) {url=this.getUrl+'/count/'+id;}
                else {url=this.getUrl+'/count';}
                this.sayUrl(url,'count');

                // if (!localStorage[`count_${this.instance}`] || localStorage[`count_${this.instance}`] === 'false') {
                axios.get(url)
                    .then(response => this.onGetTotalRecordDone(response.data))
                    .catch(error => this.onGetDataFail(error.response.data));
                // }
            },
            onGetTotalRecordDone: function(data) {
                eventBus.$emit('setTotalRecord'+this.instance,data);
                eventBus.$emit('loadingDone',this.instance);
                // localStorage[`count_${this.instance}`] = 'true';
            },
            getChildren: function (id) {
                this.setScroll(1);
                this.getData(id);
                
                eventBus.$emit('drillup',id);
                eventBus.$emit('refreshPath'+this.instance,id);

                axios.get(this.getUrl+'/children/'+id)
                    .then(response => eventBus.$emit('setLevel',response.data['level']))
                    .catch(error => this.onGetDataFail(error.response.data));
                // eventBus.$emit('toggleForm',false);
                this.gotoPage(1);
                eventBus.$emit('setCurrentPage',1);

                // this.getDataCounter = 0;
            },
            getChildrenTPS: function (id) {
                
            },
            onGetDataFail: function(data) {
                this.gridData=[];
                eventBus.$emit('loadingDone',this.instance);
                eventBus.$emit('openNotif',data);
            },
            setInstance: function() {
                eventBus.$on('getChildren'+this.instance, this.getChildren);
            },
            toggleRow: function (id) {
                const index = this.expandedRows.indexOf(id);

                let url;
                if (index > -1) {
                    this.expandedRows.splice(index, 1);
                } else {
                    eventBus.$emit(`loadingStart${this.instance}`);
                    this.loading = true;

                    this.expandedRows.push(id);

                    if (!this.collapsibleLocal) {

                        url = this.getUrl + '/detail/' + id;
                        var vm = this;

                        if (!this.rowDetails['i' + id]) {
                            axios.get(url)
                                .then(response => {
                                    vm.loading = false;
                                    vm.rowDetails['i' + id] = response.data
                                })
                                .catch(error => eventBus.$emit('openNotif', error.response.data));
                        }
                        else {
                            this.loading = false;
                        }
                    } else {
                        this.loading = false;
                    }
                    eventBus.$emit('loadingDone',this.instance);
                }
            },
            formatDecimal: function (inputString){
                return parseFloat(inputString).toFixed(2)
            },
            formatInteger: function (inputString) {
                return parseInt(inputString)
            },
            getCrudPermission: function () {
                if (this.crudPermission)
                {
                    axios.get(`${this.getUrl}/permission`)
                        .then(res => {
                            const permission = res.data;
                            this.permission.canAdd = permission.canAdd;
                            this.permission.canEdit = permission.canEdit;
                            this.permission.canDelete = permission.canDelete;
                        })
                        .catch(err => {
                            console.log("Crud permission is active and you doesn't give me the data");
                        })
                }
            },
            setToggleWidth: function() {
                this.toggle = !this.toggle
                if (this.toggle) {
                    this.myWidth = this.myWidth+150
                }
                else {
                    this.myWidth = this.myWidth-150
                }
            },
            setTableWidth: function(w) {
                this.myWidth = w
            },
            setTableWidth: function() {
                this.tableWidth=document.documentElement.clientWidth-220;
            },
            getRowHistory: function (id) {
                eventBus.$emit('getRowHistory', id);
            },
            refresh: function() {
                this.getData(this.parent);
                this.refreshState = true;
                eventBus.$emit('noScrollSearchClear', true);
            },
            refreshControl: function (data) {
                this.refreshState = !data;
            },
            setBreadcrumb: function() {
                let url = this.longUrl
                var sp = url.split("/")
                var judul1 = sp[2].toUpperCase()
                var temp_judul2 = sp[3]
                var judul2 = ''
                
                if (temp_judul2 == 'tahapan') {
                    judul2 = 'Pemilih'
                }
                else if (temp_judul2 == 'tahapankk') {
                    judul2 = 'KK'
                }
                else if (temp_judul2 == 'tahapantms') {
                    judul2 = 'TMS'
                }
                else if (temp_judul2 == 'tahapanganda') {
                    judul2 = 'Ganda'
                }
                else if (temp_judul2 == 'tahapanbaru') {
                    judul2 = 'Baru'
                }
                else if (temp_judul2 == 'tahapanubah') {
                    judul2 = 'Ubah'
                }
                
                // this.myBreadcrumb = 'Home / '+judul1+' / '+judul2
                this.myBreadcrumb = judul2
            },
            toggleResetPassword(row) {
                eventBus.$emit('resetpasswordIdentifier', row);
            }
        },
        created: function () {
            if (this.addBreadcrumb) {
                this.setBreadcrumb()
            }
            if (this.recursive) {
                let param = this.selected ? this.selected : -1;
                this.getData(param);
            } else {
                this.getData();
            }
            eventBus.$emit('refreshPath'+this.instance,-1);
            eventBus.$on('changepage' + this.instance, this.gotoPage);
            eventBus.$on('formFail', this.errorMessage);
            eventBus.$on('refreshData', this.getData);
            eventBus.$on('refreshData'+this.instance, this.getData);
            eventBus.$on('setInstance', this.setInstance);
            eventBus.$on('getChildren'+this.instance, this.getChildren);
            // eventBus.$on('getChildren', this.getChildren);
            eventBus.$on('scroll', this.scrolling);
            eventBus.$on('setTableWidth', this.setTableWidth);
            eventBus.$on('noScrollSearch' + this.instance, this.loadData);
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
            this.getCrudPermission();
            eventBus.$on('refreshTags'+this.instance, this.getTaggedData);
            eventBus.$on('reload', this.reload);
            eventBus.$on('resetChecked', this.resetChecked);
            eventBus.$on('togelParent', this.setToggleWidth);
            eventBus.$on('noScrollSearchState', this.refreshControl);
        },
        directives: {
            decimal: {
                update: function (el, binding) {
                    el.value = parseFloat(el.value).toFixed(2);
                    binding.value = el.value;
                }
            }
        },
        watch: {
            allSelected: function () {
                let selected = {};
                if (this.allSelected) {
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
            }
        }
    }
</script>

<style scoped>
    td > i.text-primary {
        cursor: pointer;
        font-size: 18px;
    }
    .text-primary {
        color: #17a2b8 !important;
    }
    .row-detail {
        background-color: #f8f8f8;
    }
    tfoot {
        text-transform: uppercase;
    }

    thead > tr > th {
        font-size : 14px;
    }

    tbody > tr > td {
        font-weight: 500;
    }
    .is-selected {
        background-color: rgba(255, 127, 80, 0.6);
        color: #fff;
    }
    .custom-control-input:checked~.custom-control-label::before {
        color: #fff;
        border-color: darkorange;
        background-color: darkorange;
    }
    .custom-checkbox
    .custom-control-input:checked~
    .custom-control-label::after {
        background-color: darkorange;
    }
</style>