<template>
    <b-dropdown id="tags" text="Tags" size="sm" variant="success" right >
        <b-row>
            <b-col>
                <b-form-input
                        v-model="search"
                        autocomplete="off"
                        type="search"
                        placeholder="Cari tag...">
                </b-form-input>
            </b-col>
        </b-row>
        <b-dropdown-divider></b-dropdown-divider>
        <b-dropdown-item v-for="item in options"
                         @click="setTag('setTag', source_id, item.id)"
                         v-bind:key="item.id"
                         :disabled="item.disabled">
            {{item[tagCaption]}}
        </b-dropdown-item>

        <b-dropdown-item v-if="options.length === 0" :disabled="true">
            Tag tidak ditemukan
        </b-dropdown-item>
    </b-dropdown>
</template>

<script>
    module.exports = {
        name: 'bs4tagging',
        props: {
            getUrl: String,
            postUrl: String,
            source_id: Number,
            instance: {
                type: String,
                default: ""
            },
            tagLimit: Number,
            tagWhen: {
                type: Array,
                default: function () {
                    return []
                }
            },
            tagCaption: String
        },
        data() {
            return {
                isActive: false,
                gridData: [],
                form: new Form(),
                search: ''
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
                    return this.gridData.length <= this.tagLimit;
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
                // if (data['callback']) {this[data['callback']]();}
            },
            setTag: function (cmd,source_id,target_id,id="") {
                this.isActive=false;
                this.form['cmd'] = cmd;
                this.form['source_id'] = source_id;
                if (cmd === "setTag") {
                    this.form['target_id'] = target_id;
                } else {
                    this.form['id'] = id;
                }
                const role = this.basename(location.pathname);
                const postUrl = this.postUrl + `?role=${role}`;
                this.form.submit('post', postUrl)
                    .then(data => this.formSuccess(data))
                    .catch(error => this.formFail(error));
            },
            getData: function () {
                if (!this.isActive) {
                    this.isActive=true;
                    const url=this.getUrl+'/table/1/-1';
                    axios.get(url)
                        .then(response => this.loadData(response.data))
                        .catch(error =>  {
                            if (error.hasOwnProperty('response') && error.response.hasOwnProperty('data')) {
                                eventBus.$emit('openNotif',error.response.data);
                            }
                        });
                } else {
                    this.isActive=false;
                }
            },
            unSetTag: function(data) {
                this.setTag('unSetTag',this.source_id,0,data);
            },
            loadData: function(data) {
                if (data) {
                    const label = data[0].level_label;
                    if (this.tagWhen.length > 0) {
                        if(!this.tagWhen.includes(label)) {
                            data = [];
                            const row = {};
                            row.id = 0;
                            row.nama = 'Not allowed at this level of `ref`';
                            row.disabled = true;
                            data.push(row);
                        }
                    }
                    // data.forEach(row => {
                    //     if (this.tagWhen.length > 0) {
                    //         row['disabled'] = !this.tagWhen.includes(row.level_label);
                    //     }
                    // });
                }
                this.gridData=Array.from(Object.keys(data), k=>data[k]);
            },
            basename (path) {
                return path.split('/').reverse()[0];
            }
        },
        created: function() {
            eventBus.$on('unSetTag'+this.source_id, this.unSetTag);
        },
        mounted: function() {
            this.$root.$on('bv::dropdown::show', bvEvent => {
                this.getData();
            });
            this.$root.$on('bv::dropdown::hide', bvEvent => {
                this.isActive = false;
            })
        },
        computed: {
            criteria: function() {
                return this.search.trim().toLowerCase()
            },
            options: function () {
                const criteria = this.criteria;
                const options = this.gridData;
                if (criteria) {
                    return options.filter(opt => {
                        let str = '';
                        if (opt.hasOwnProperty('nama')) {
                            str = opt.nama;
                        } else if(opt.hasOwnProperty('fullname')) {
                            str = opt.fullname;
                        }
                        return str.toLowerCase().indexOf(criteria) > -1
                    });
                }
                return options
            }
        }
    }
</script>