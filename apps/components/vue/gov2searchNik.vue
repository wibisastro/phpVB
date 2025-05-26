<template>
<div class='box'>
    <div class="field has-addons">
      <div class="control">
        <input class="input" type="text" placeholder="Masukkan NIK" v-model="nik">
      </div>
      <div class="control">
        <a class="button is-info" @click="submitSearch">
          Cari Pemilih
        </a>
      </div>
    </div>
    <div class="notification" v-if="isNotif" :class="notifClass">
      <button class="delete" @click="isNotif=false"></button>
        <p v-text="notifText"></p>
    </div>
    <div v-if="isResult">
    <table class="table is-striped">
        <thead><th></th><th></th><th><button class="delete" @click="isResult=false"></button></th></thead>
      <tbody>
        <tr v-for="(val,key) in results">
          <th>{{ key }}</th>
          <td>{{ val }}</td>
        <td></td>
        </tr>
      </tbody>
    </table>
    </div>
</div>
    
</template>

<script>
module.exports = {
    name: "gov2searchNik",
    props: {
        postUrl: String,
        source: String,
    },
    data: function() {
      return {
          nik:"",
          form: new Form(),
          isNotif: false,
          notifClass: '',
          notifText: '',
          results:[],
          isResult: false
      }
    },
    methods: {
        openNotif: function(data) {
            this.isResult=false;
            this.isNotif=true;
            this.notifText=data['notification'];
            this.notifClass=data['class'];
        },
        openResult: function(data) {
            this.isNotif=false;
            this.isResult=true;
            this.results=data;
        },
        submitSearch: function () {
            eventBus.$emit('loadingStart','searchNIK');
            this.form['cmd'] = 'search';
            this.form['nik'] = this.nik;
            if (typeof this.source==='undefined' || this.source=='snapshot') {
                var url=this.postUrl+'/dpsnapshot/search';
            } else if (this.source=='aktif') {
                var url=this.postUrl+'/dpaktif';
            } else if (this.source=='prov') {
                var url=this.postUrl+'/monitoringdp/search';
            } else if (this.source=='dps') {
                var url=this.postUrl+'/sidalih/dpsnik';
            } else if (this.source=='publik') {
                var url=this.postUrl+'/dppublik/dpsnik';
            }
            this.form.submit('post',url)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
        formSuccess: function (data) {
            this.openResult(data);
            eventBus.$emit('loadingDone','searchNIK');
        },
        formFail: function (data) {
            this.openNotif(data);
        },
    },
    created: function () {
        
    } 
  }
</script>

<style src="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css"></style>

<style>   
    .hero.is-warning {
    background-color: coral;
    }
</style>