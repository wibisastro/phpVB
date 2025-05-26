<template>
    <div class="row" v-if="isActive" data-test="breadcrumb-box">
        <div class="col-lg-12">
            <div class="block no-bottom" style="margin: 10px;">
                <div class="alert alert-info" style="margin-bottom: 10px;">
                    <table>
                        <tr v-for="path in pathData">
                            <td><strong class="alert-link">{{ setLevel(path['level_label']) }}</strong></td>
                            <td style="padding-left: 20px;">:
                                <a v-if="link" class="use-pointer breadcrumb-text text-info"
                                   @click="getBack(path['id'])"
                                   v-text="path['caption']" ></a>
                                <span v-else v-text="path['caption']" class="breadcrumb-text text-info" >
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

module.exports = {
    name: "gov2breadcrumb",
    components: {
        'drillup': httpVueLoader('./drillup.vue'),
    },
    props: {
        pathUrl: String,
        isHorizontal: Boolean,
        isActive: Boolean,
        instance: {
            type: String,
            default: ""
        },
        urlListener: String,
        link: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            pathData: [],
            isMyActive: false,
            onInstance: ""
        }
    },
    methods: {
        setLevel: function (data) {
            return data.split('_').join(' ').toUpperCase();
        },
        loadData: function (data) {
            if (data) {
                this.pathData = Array.from(Object.keys(data), k => data[k]);
                // console.log(this.pathData[this.pathData.length-1])
                if(this.pathData.length >=2) {
                    // eventBus.$emit('drillup',this.pathData[this.pathData.length-2]);
                }

                eventBus.$emit('lastLevel',this.pathData[this.pathData.length-1]);
            }
            this.isMyActive = this.pathData.length > 0;
        },
        getData: function (id) {
            let url;
            if (id) { url = this.pathUrl + '/' + id }
            else { url = this.pathUrl }

            let printUrl;
            let linger = url.indexOf('-1');
            let reset = url.indexOf('-2');

            if (linger > 0 || reset > 0)
            {
                printUrl = 'Invalid';
            } else {
                printUrl = url.replace(this.instance + '/', '');
            }

            eventBus.$emit('printUrl' + this.urlListener, printUrl);
            axios.get(url)
                .then(response => this.loadData(response.data))
                .catch(error => this.onGetDataFail(error.response.data));
        },
        onGetDataFail: function (data) {
            eventBus.$emit('openNotif', data);
        },
        getBack: function (data) {
            if (this.instance) {
                eventBus.$emit('setInstance', this.instance);
                eventBus.$emit('setGetUrl', this.instance);
            }
            if (data == 0) { data = -2; }
            // if (lvl == 0) {
            eventBus.$emit('getChildren' + this.instance, data);
            // }
            // else {
            //     eventBus.$emit('getChildren' + lvl, data);
            // }

            this.getData(data);
        },
        activeInstance: function (data) {
            this.onInstance = data;
        }
    },
    created: function () {
        // console.log(this.disabled);
        if(this.instance)
        {
            this.getData(-1);
        } else {
            this.getData();
        }

        if (!this.instance) {
            eventBus.$on('refreshPathprogram', this.getData);
            eventBus.$on('refreshPathop', this.getData);
            eventBus.$on('refreshPathsp', this.getData);
            eventBus.$on('refreshPathkkl', this.getData);
            eventBus.$on('refreshPathiop', this.getData);
            eventBus.$on('refreshPathikp', this.getData);
            eventBus.$on('refreshPathsk', this.getData);
            eventBus.$on('refreshPathokp', this.getData);
            eventBus.$on('refreshPathoknp', this.getData);
            eventBus.$on('refreshPathikk', this.getData);
            eventBus.$on('refreshPathiknp', this.getData);
            eventBus.$on('refreshPathiok', this.getData);
            eventBus.$on('refreshPathso', this.getData);
            eventBus.$on('refreshPathkomponen', this.getData);
            eventBus.$on('refreshPathlokasi', this.getData);
            eventBus.$on('refreshPathsatker', this.getData);
            eventBus.$on('refreshPathalokasi', this.getData);
            eventBus.$on('refreshPathls', this.getData);
            eventBus.$on('refreshPathas', this.getData);
            eventBus.$on('refreshPathtarget', this.getData);

            eventBus.$on('refreshPathprog', this.getData);
            eventBus.$on('refreshPathkeg', this.getData);

            eventBus.$on('refreshPathkegiatan', this.getData);

            eventBus.$on('refreshPath', this.getData);
        } else {
            eventBus.$on('refreshPath' + this.instance, this.getData)
        }

        eventBus.$on('onInstance', this.activeInstance);
    }
}
</script>

<style scoped>
a {
    cursor: pointer;
}
.myAuto {
    margin-top: auto;
    margin-bottom: auto;
}
</style>