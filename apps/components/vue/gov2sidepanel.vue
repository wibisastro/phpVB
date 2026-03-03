<template>
<div>
    <div class="offcanvas offcanvas-end" :class="{ 'show': isActive }" :style="{ visibility: isActive ? 'visible' : 'hidden' }">
      <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title fw-semibold">Gov2 Cloud: {{ instance }}</h5>
        <button type="button" class="btn-close btn-close-white" @click="closeSidePanel()"></button>
      </div>

      <div class="offcanvas-body">
            <widget :is-horizontal="true" path-url="aktif/breadcrumb" get-url="aktif" instance="aktif" :independent="true" v-if="instance=='Push'"></widget>
            <widget :is-horizontal="true" path-url="pindah/breadcrumb" get-url="pindah" instance="pindah" :independent="true" v-if="instance=='PullReq'"></widget>
            <table class="table table-striped table-sm table-hover w-100" v-if="instance=='Push'">
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
            <table class="table table-striped table-sm table-hover w-100" v-if="instance=='PullReq'">
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
            <table class="table table-striped table-sm table-hover w-100" v-if="instance=='Pull'">
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
            <div class="row">
                <div class="col-md-6"></div>
                <div class="col-md-6">
                    <a class="btn btn-warning d-block" @click="onSubmit" v-if="instance=='Push'"
                       title="Pindah memilih akan diberi kode 31">
                        <i class="fa fa-cut"></i>
                        <span>Pindahkan Pemilih</span>
                    </a>
                    <a class="btn btn-warning d-block" @click="onApproved" v-if="instance=='Pull'"
                       title="Pindah memilih akan diberi kode 31">
                        <i class="fa fa-check"></i>
                        <span>Setujui Tarik Data</span>
                    </a>
                    <a class="btn btn-warning d-block" @click="onPullRequest" v-if="instance=='PullReq'"
                       title="Kirim Permintaan Tarik Data ke Wilayah di atas">
                        <i class="fa fa-check"></i>
                        <span>Tarik Data</span>
                    </a>
                </div>
            </div>
      </div>

      <div class="p-3 border-top text-muted small">
          {{ footNote }}
      </div>
    </div>
    <div class="offcanvas-backdrop fade show" v-if="isActive" @click="closeSidePanel()"></div>
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

<style>

</style>