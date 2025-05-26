<template>
<div>
    <div class="quickview" :class="{ 'is-active': isActive }">
      <header class="quickview-header is-primary">
        <p class="title">Gov2 Cloud: {{ instance }}</p>
        <span class="delete" data-dismiss="quickview" @click="closeSidePanel()"></span>
      </header>

      <div class="quickview-body">
        <div class="quickview-block">
            <widget :is-horizontal="true" path-url="aktif/breadcrumb" get-url="aktif" instance="aktif" :independent="true" v-if="instance=='Push'"></widget>
            <widget :is-horizontal="true" path-url="pindah/breadcrumb" get-url="pindah" instance="pindah" :independent="true" v-if="instance=='PullReq'"></widget>
            <table class="table is-striped is-narrow is-hoverable is-fullwidth" v-if="instance=='Push'">
                  <thead>
                    <tr>
                        <th>No</th>
                        <th>ID</th>
                        <th>Nama</th>
                    </tr>
                  </thead>
                <tbody>
                    <tr v-for="(val,key) in pushData">
                        <td>{{ key+1 }}</td>
                        <th>{{ val['id'] }}</th>
                        <td>{{ val['nama'] }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="table is-striped is-narrow is-hoverable is-fullwidth" v-if="instance=='PullReq'">
                  <thead>
                    <tr>
                        <th>No</th>
                        <th>ID</th>
                        <th>Nama</th>
                    </tr>
                  </thead>
                <tbody>
                    <tr v-for="(val,key) in pushData">
                        <td>{{ key+1 }}</td>
                        <th>{{ val['id'] }}</th>
                        <td>{{ val['nama'] }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="table is-striped is-narrow is-hoverable is-fullwidth" v-if="instance=='Pull'">
                  <thead>
                    <tr>
                        <th>No</th>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Tujuan</th>
                    </tr>
                  </thead>
                <tbody>
                    <tr v-for="(val,key) in pushData">
                        <td>{{ key+1 }}</td>
                        <th>{{ val['id'] }}</th>
                        <td>{{ val['nama'] }}</td>
                        <td>
                            <ul>
                                <li>{{ val['kab'] }}</li>
                                <li>{{ val['kec'] }}</li>
                                <li>{{ val['kel'] }}</li>
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="columns">
                <div class="column is-6">
                    
                </div>
                <div class="column is-6">
                    <a class="button is-block is-warning" @click="onSubmit" v-if="instance=='Push'">
                       <b-tooltip is-small label="Pindah memilih akan diberi kode 31">
                        <span class="icon">
                          <i class="fa fa-cut"></i>
                        </span>
                        <span>Pindahkan Pemilih</span>
                        </b-tooltip>
                    </a>
                    <a class="button is-block is-warning" @click="onApproved" v-if="instance=='Pull'">
                       <b-tooltip is-small label="Pindah memilih akan diberi kode 31">
                        <span class="icon">
                          <i class="fa fa-check"></i>
                        </span>
                        <span>Setujui Tarik Data</span>
                        </b-tooltip>
                    </a>
                    <a class="button is-block is-warning" @click="onPullRequest" v-if="instance=='PullReq'">
                       <b-tooltip is-small label="Kirim Permintaan Tarik Data ke Wilayah di atas">
                        <span class="icon">
                          <i class="fa fa-check"></i>
                        </span>
                        <span>Tarik Data</span>
                        </b-tooltip>
                    </a>
                </div>
            </div>
        </div>
      </div>

      <footer class="quickview-footer">
          {{ footNote }}
      </footer>
    </div>
</div>
</template>

<script>
module.exports = {
    name: 'gov2sidepanel',
    components: {
        'widget': httpVueLoader('/wilayahkpu/vue/widget.vue'),  
    },
    props:  {
        action: String,
        instance: String
    },
    data: function () {
      return { 
          isActive: '',
          footNote: '',
          pushData: [],
          form: new Form()
      }
    },
    methods: {
        openSidePanel(data) {
            this.isActive=true;
            this.footNote=data['notification'];
            this.pushData=data['data'];
        },
        closeSidePanel() {
            this.isActive=false;
            this.footNote='';
            this.pushData=[];
        },
        onSubmit: function () {
            this.form['cmd']='pushRequest';            
            this.form['pindah']=this.pushData;
            this.form.submit('post',this.action)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
        onPullRequest: function () {
            this.form['cmd']='pullRequestSend';            
            this.form['pindah']=this.pushData;
            this.form.submit('post',this.action)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
        formSuccess: function (data) {
            this.closeSidePanel();
            eventBus.$emit('refreshData',data['kel_id']);
            eventBus.$emit('openNotif',data);
        },
        formFail: function (data) {
            this.closeSidePanel();
            eventBus.$emit('openNotif',data);
        },
        onApproved: function () {
            this.form['cmd']='pullApproved';            
            this.form['pindah']=this.pushData;
            this.form.submit('post',this.action)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
    },
    created: function () {
        eventBus.$on('openSidePanel'+this.instance, this.openSidePanel);
        eventBus.$on('closeSidePanel'+this.instance, this.closeSidePanel);
    }
}
</script>

<style src="/css/bulma-quickview.min.css">

</style>