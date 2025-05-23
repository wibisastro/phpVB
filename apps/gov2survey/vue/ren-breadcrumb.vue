<template>
    <div class="row" v-if="isActive" data-test="breadcrumb-box">
        <div class="col-lg-12">
            <div class="block no-bottom" style="margin: 10px;">
                <div class="alert alert-info" style="margin-bottom: 10px;">
                    <table>
                        <tr v-for="(path, index) in pathData" v-bind:key="index + 1">
                            <td><strong class="alert-link">{{ setLevel(path['level_label']) }}</strong></td>
                            <td style="padding-left: 20px;">:
                                <a class="use-pointer breadcrumb-text text-info"
                                   @click="getBack(path['id'])"
                                   v-text="path['caption']" ></a>
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
    name: "ren-breadcrumb",
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
        urlListener: String
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
        if(this.instance) {
            this.getData(-1);
        } else {
            this.getData();
        }

        if (!this.instance) {
            eventBus.$on('refreshPath', this.getData);

            eventBus.$on('refreshPathkuesioner_diklat', this.getData);
            eventBus.$on('refreshPathkuesioner_pertanyaan', this.getData);
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