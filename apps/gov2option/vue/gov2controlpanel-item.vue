<template>
  <div>
    <b-card-header class="pointer" role="tab" @click="toggleCollapse(cluster)">
      {{cluster.app | uppercase}}
      <b-spinner variant="info" small v-if="loading"></b-spinner>
      <span class="subtitle" v-if="cluster.keterangan">[{{cluster.keterangan}}]</span>
    </b-card-header>

    <b-collapse :id="`collapse-${cluster.id}`" role="tabpanel" accordion="my-accordion">
      <div class="card-body">

        <div class="form-group" v-for="(c, index) in child">

          <div class="form-check form-check-inline checkbox-nice" v-if="c.type==='checkbox'">
            <input class="form-check-input" type="checkbox" :id="`inlineCheckbox${c.id}`"
                   v-model="child[index].value" :disabled="c.status === 'off'">
            <label class="form-check-label" :for="`inlineCheckbox${c.id}`">{{c.nama}}</label>
          </div>

          <div class="radio" v-if="c.type==='radio'">
            <input type="radio" name="optionsRadio" v-model="selected" :value="c.id"
                   :disabled="c.status === 'off'"
                   :id="`optionsRadio${c.id}`">
            <label :for="`optionsRadio${c.id}`">
              {{c.nama}}
            </label>
          </div>

          <label :for="`input-${c.id}`" v-if="c.type==='textbox' || c.type==='text'">{{c.nama | capitalize}}</label>
          <b-form-textarea v-if="c.type==='textbox'"
                           :id="`exampleTextarea${c.id}`"
                           v-model="child[index].value"
                           placeholder="Enter something..."
                           rows="3"
                           max-rows="6"
                           maxlength="255"
                           :disabled="c.status === 'off'"
          ></b-form-textarea>
          <input v-if="c.type==='text'" :id="`input-${c.id}`" type="text" class="form-control"
                 v-model="child[index].value" :disabled="c.status === 'off'">
          <span class="help-block" v-if="(c.type==='textbox' || c.type==='text') && c.keterangan">
                {{c.keterangan}}
              </span>

        </div>
        <b-button variant="primary" squared @click="save" :disabled="loading" v-if="true">Save</b-button>
        <b-button variant="primary" squared @click="confirmDel" :disabled="loading" v-else>Delete</b-button>
      </div>
    </b-collapse>
  </div>
</template>

<script>

module.exports = {
  name: 'gov2controlpanel-item',
  props:  {
    cluster: Object,
    getUrl: String,
    app: String,
    type: String,
    portal: String
  },
  data: function () {
    return {
      status: [],
      collapsed: {},
      child: [],
      loading: false,
      selected: '',
      values: []
    }
  },
  methods: {
    toggleCollapse: function (cluster) {
      if(this.child.length < 1) {
        this.getData(cluster);
      }
      this.collapsed[cluster.id] = !this.collapsed[cluster.id];
      this.$root.$emit('bv::toggle::collapse', `collapse-${cluster.id}`)
    },
    getData: function (cluster) {
      this.loading = true;
      const url = `${this.getUrl}/options/${this.portal}/${cluster.id}`;
      axios.get(url)
          .then(resp => {
            this.child = Array.from(Object.keys(resp.data), k=>resp.data[k]);
            resp.data.forEach(row => {
              if (row && row.type === 'radio') {
                if (row.value !== "" && row.value !== null) {
                  this.selected = row.id;
                }
              }
            });
            this.loading = false;
          })
          .catch(e => {
            console.log(e);
            this.loading = false;
          })
    },
    save: function () {
      this.loading = true;

      //if there's radio fields in the fieldset.
      if (this.selected) {
        this.child.forEach((row, index) => {
          if (row.type === 'radio') {
            if (row.id === this.selected) {
              this.child[index].value = 1;
            } else {
              this.child[index].value = "";
            }
          }
        })
      }
      const rows = this.child;
      let data = {cmd: 'save', data: {portal: this.portal, data: rows}};
      axios.post(this.getUrl, data)
          .then(resp => {
            this.loading = false;
            eventBus.$emit('openNotif', resp.data);
          })
          .catch(e => {
            console.log(e);
            this.loading = false;
          });
    },
    del: function() {
      this.loading = true;
      const url = `${this.getUrl}/service_del/${this.cluster.id}`;
      axios.get(url)
          .then(resp => {
            eventBus.$emit('openNotif', resp.data);
            this.loading = false;
          })
          .catch(e => {
            console.log(e);
            this.loading = false;
          })
    },
    confirmDel : function () {
      const el = this.$createElement;
      const message = el('div', {domProps: {class: 'row'}}, [
        el('p',{class: 'text-center'},
            'Apakah anda yakin ingin menghapus service ini ? ')
      ]);
      this.$bvModal.msgBoxConfirm(message, {
        title: 'Hapus Service',
        centered: true,
        size: 'sm',
        okVariant: 'danger'
      })
          .then(ok => {
            if (ok) {
              this.del();
            }
          }).catch(e => console.log(e));
    },
  },
  mounted() {

  },
  filters: {
    capitalize: function (value) {
      if (!value) return ''
      value = value.toString()
      return value.charAt(0).toUpperCase() + value.slice(1)
    },
    uppercase: function (value) {
      if (!value) return ''
      value = value.toString()
      return value.toUpperCase()
    }
  }
}
</script>
<style scoped>
.pointer {
  cursor: pointer;
}
.subtitle {
  font-size: 14px;
  color: darkgray;
  float: right;
}

.btn {
  display: inline-block;
  font-weight: 400;
  color: #212529;
  text-align: center;
  vertical-align: middle;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  background-color: transparent;
  border: 1px solid transparent;
  padding: .375rem .75rem;
  font-size: 1rem;
  line-height: 1.5;
  border-radius: .25rem;
  transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.help-block {
  font-size: 0.82em;
  color: #9a9898;
}

.badge {
  display: inline-block;
  padding: .25em .4em;
  font-size: 75%;
  font-weight: 700;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: .25rem;
  transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.badge-pill {
  padding-right: .6em;
  padding-left: .6em;
  border-radius: 10rem;
}

</style>