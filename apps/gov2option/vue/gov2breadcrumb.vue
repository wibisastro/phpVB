<template>
    <div class="breadcrumb-nav" v-if="isActive && pathData.length > 0" data-test="breadcrumb-box">
        <div class="breadcrumb-trail">
            <div v-for="(path, index) in pathData"
                 :key="index"
                 class="breadcrumb-item-row"
                 :style="{ paddingLeft: (index * 1.5) + 'rem' }">
                <i class="fa breadcrumb-icon"
                   :class="index === 0 ? 'fa-home' : 'fa-level-up fa-rotate-90'"></i>
                <span class="breadcrumb-level">{{ setLevel(path['level_label']) }}</span>
                <i class="fa fa-chevron-right breadcrumb-sep"></i>
                <a v-if="link"
                   class="breadcrumb-link"
                   @click="getBack(path['id'])">{{ path['caption'] }}</a>
                <span v-else class="breadcrumb-caption">{{ path['caption'] }}</span>
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
.breadcrumb-nav {
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    border: 1px solid #d4ddf7;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 0.75rem;
}
.breadcrumb-trail {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.breadcrumb-item-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.4;
}
.breadcrumb-icon {
    color: #5b4fb9;
    font-size: 0.85rem;
    width: 1rem;
    text-align: center;
    flex-shrink: 0;
}
.breadcrumb-level {
    font-weight: 700;
    color: #1a1a2e;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.03em;
    white-space: nowrap;
}
.breadcrumb-sep {
    color: #b0b8d1;
    font-size: 0.65rem;
    flex-shrink: 0;
}
.breadcrumb-link {
    color: #5b4fb9;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
}
.breadcrumb-link:hover {
    color: #4338a0;
    text-decoration: underline;
}
.breadcrumb-caption {
    color: #5b4fb9;
    font-weight: 500;
}
</style>