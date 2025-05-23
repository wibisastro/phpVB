<template>
  <div>
    <b-card-header class="pointer" role="tab" @click="toggleCollapse(cluster.id)">
      {{cluster.nama}}&nbsp;<span class="badge badge-danger badge-pill" v-if="expiry"> Expired</span>
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
        <b-button variant="primary" squared @click="save" :disabled="loading" v-if="!expiry">Save</b-button>
        <b-button variant="primary" squared @click="confirmDel" :disabled="loading" v-else>Delete</b-button>
      </div>
    </b-collapse>
  </div>
</template>

<script>

module.exports = {
  name: 'gov2option-item',
  props:  {
    cluster: Object,
    getUrl: String,
    app: String,
    type: String
  },
  data: function () {
    return {
      status: [],
      collapsed: {},
      child: [],
      loading: false,
      selected: '',
      values: [],
      expiry: null
    }
  },
  methods: {
    toggleCollapse: function (id) {
      if(this.child.length < 1) {
        this.getData();
      }
      this.collapsed[id] = !this.collapsed[id];
      this.$root.$emit('bv::toggle::collapse', `collapse-${id}`)
    },
    getData: function () {
      this.loading = true;
      const url = `/gov2option/${this.app}/option/${this.type}/${this.cluster.id}`;
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
    getExpiration: function() {
      this.loading = true;
      const url = `/gov2option/${this.app}/option/service_expiry/${this.cluster.id}`;
      axios.get(url)
          .then(resp => {
            if (resp.data) {
              this.expiry = resp.data.expired;
              eventBus.$emit('gov2option-refresh');
            }
            this.loading = false;
          })
          .catch(e => {
            console.log(e);
            this.loading = false;
          })
    },
    save: function () {
      this.loading = true;
      const url = `/gov2option/${this.app}/option`;
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
      let data = {cmd: 'save1', data: rows};
      axios.post(url, data)
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
      const url = `/gov2option/${this.app}/option/service_del/${this.cluster.id}`;
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
    this.getExpiration();
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