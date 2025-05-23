<template>
  <li class="dropdown d-none d-md-block" :class="[status ? 'show' : '']" >
    <template  v-if="isShowListPortal">
      <a class="btn">{{ caption }} : {{portalName ? portalName : unitName | ellipsis}}</a>
    </template>
    <template v-else>
      <a class="btn dropdown-toggle" data-toggle="dropdown" @click="toggle">{{ caption }} : {{portalName ? portalName : unitName | ellipsis}}</a>
      <ul class="dropdown-menu" :class="[status ? 'show scrollable' : '']">
        <li v-if="resetable && (portalName || unitName)">
          <a href="#" class="dropdown-toggle dropdown-nocaret"
            @click="goto(`${changePortalAddr}/${getResetCmd}?next=${nextUrl}`)">
            <i class="fa fa-window-close-o"></i><span>Reset Selected Option</span></a>
        </li>
        <b-dropdown-divider v-if="resetable && (portalName || unitName)"></b-dropdown-divider>
        <li>
          <b-form-input v-model="search" type="search" placeholder="Search..."></b-form-input>
        </li>
        <b-dropdown-divider></b-dropdown-divider>
        <li  v-for="(es1, es1i) in options" v-bind:key="es1i + 1">
          <a href="#" class="dropdown-toggle dropdown-nocaret">
            <i class="fa fa-external-link"></i>
            <span @click="goto(`${changePortalAddr}/${getCmd}/${es1.id};${es1.portal};${es1.nama}?next=${nextUrl}`)">
              <b>{{ es1.kode }}</b> - {{ es1.nama }}</span>
          </a>
        </li>
        <li v-if="fetchFail">
          <a class="dropdown-toggle dropdown-nocaret"><i class="fa fa-warning"></i>Gagal mengambil data portal</a>
        </li>
        <li v-else-if="data.length === 0">
          <a class="dropdown-toggle dropdown-nocaret"><i class="fa fa-info-circle"></i>Belum ada data portal</a>
        </li>
      </ul>
    </template>
  </li>
</template>

<script>

module.exports = {

  name: 'bs4-menu-portal',
  data: function() {
    return {
      data: [],
      toggleMenu: [],
      fetchFail: {
        type: Boolean,
        default: false
      },
      hiddenPortalCode: ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'],
      search: '',
      roles: '',
      taggingOpd: []
    }
  },
  props: {
    name: String,
    status: Boolean,
    root: String,
    portalName: {
      type: String,
      default: ''
    },
    caption: {
      type: String,
      default: 'OPD'
    },
    getUrlApp: {
      type: String,
      default: 'boardrenjakl'
    },
    getUrl: {
      type: String,
      default: 'index'
    },
    getPortalCmd: {
      type: String,
      default: 'getPortalList'
    },
    getCmd: {
      type: String,
      default: 'changePortal'
    },
    unitName: {
      type: String,
      default: ''
    },
    resetable: {
      type: Boolean,
      default: false
    },
    getResetCmd: {
      type: String,
      default: 'option_reset'
    },
    changePortalUrl: {
      type: String,
      default: ``
    },
    isTaggingOpd: {
      type: String,
      default: null
    },

  },
  methods: {
    toggle: function() {
      eventBus.$emit('togelMenu', this.name);
    },
    getData: function () {
      const url = `${this.root}/${this.getUrlApp}/${this.getUrl}/${this.getPortalCmd}`;

      axios.get(url)
          .then(resp => {
            resp.data.forEach(r => {
              this.toggleMenu.push({open: false})
            })
            this.data = resp.data;
            this.fetchFail = false;
          })
          .catch(e => this.fetchFail = true)
    },
    getListConfig: function () {
      const url = `${this.root}/${this.getUrlApp}/${this.getUrl}/getListConfig`;
      axios.get(url)
          .then(resp => {
            var data = resp.data;
            if(data){
              this.roles = data.userRole;
              this.taggingOpd = data.tagging_opd;
            }
          })
          .catch(e => console.log(e))
    },
    goto(url) {
      this.$window.location.href = url;
    }
  },
  created: function () {
    this.getData();
    this.getListConfig();
  },
  computed: {
    isShowListPortal: function(){
      var taggingOpd = this.taggingOpd;
      var role = this.roles;
      var result = false;

      if(role == ''){
        result = true;
      }else if(taggingOpd){
        if(role == 'admin' && taggingOpd['opd_portal'] != 'bps'){
          result = true;
        }else if(role == 'member'){
          result = true;
        }
      }

      return result;

    },
    options: function () {
      if (this.data.length > 0) {
        const query = this.search.toLowerCase();
        if (query && query.length) {
            return this.data.filter(function (row) {
              return Object.keys(row).some(function (key) {
                return String(row[key]).toLowerCase().indexOf(query) > -1
              })
            })
        }
        return this.data;
      } else {
        return []
      }
    },
    nextUrl() {
      const w = this.$window.location;
      const pathname = w.pathname;
      const host = `${w.protocol}//${w.hostname}`
      if (pathname !== '/' && pathname !== '') {
        return `${host}${pathname}`
      } else {
        return host;
      }
    },
    changePortalAddr() {
      return this.changePortalUrl ? this.changePortalUrl : `${this.root}/${this.getUrlApp}/${this.getUrl}`
    }
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
    },
    ellipsis: function(value) {
      const l = value.length;
      const max_l = 52;

      if (l > max_l) {
        const words = value.split(" ");
        const fs = words.slice(0, 3);
        const fs_l = fs.length;
        const index_last_fs = value.indexOf(fs_l - 1);
        let temp = fs.join(" ");
        const rest_l = value.length - (max_l - temp.length);
        const ls = words.slice(3, words.length).join(" ");
        const temp_ls = ls.slice(index_last_fs + (rest_l - (max_l - temp.length) + 3), l);

        value = [temp, "...", temp_ls].join(" ");
      }
      return value;
    }
  }

}

</script>
<style>
.item:hover {
  background-color: whitesmoke;
}

.item a {
  font-size: 0.875em;
  color: #707070;
}

.display-block {
  display: block;
}

.display-none {
  display: none;
}

.submenu {
  list-style: none;
}
.scrollable {
  max-height: 500px;
  overflow-y: auto;
}
</style>