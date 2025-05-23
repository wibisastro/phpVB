<template>
  <li class="dropdown d-none d-md-block" :class="[status ? 'show' : '']" >
    <a class="btn dropdown-toggle" data-toggle="dropdown" @click="toggle">
      {{ caption }} : {{portalName ? portalName : unitName | ellipsis}}
    </a>
    <ul class="dropdown-menu" :class="[status ? 'show scrollable' : '']">

      <li v-if="resetable && (portalName || unitName)">
        <a href="#" class="dropdown-toggle dropdown-nocaret"
           @click="goto(`${root}/${getUrlApp}/${getUrl}/${getResetCmd}?next=${nextUrl}`)">
        <i class="fa fa-window-close-o"></i><span>Reset Selected Option</span></a>
      </li>
      <b-dropdown-divider v-if="resetable && (portalName || unitName)"></b-dropdown-divider>

      <li  v-for="(es1, es1i) in options">

        <a href="#" class="dropdown-toggle dropdown-nocaret" @click="toggleMenu[es1i].open=!toggleMenu[es1i].open">
          <i class="fa fa-folder"></i>
          <span @click="goto(`${root}/${getUrlApp}/${getUrl}/${getCmd}/${es1.id};${es1.portal};${es1.nama}?next=${nextUrl}`)">
            <b>{{ es1.kode }}</b> - {{ es1.nama }}</span>
          <i class="drop-icon" :class="[toggleMenu[es1i].open ? 'fa fa-angle-down' : 'fa fa-angle-right']"></i>
        </a>

        <ul class="submenu" :class="[toggleMenu[es1i].open ? 'display-block' : 'display-none']"
            v-if="es1.children.length > 0">
          <li class="item" v-for="(es2, es2i) in es1.children">
            <a href="#" @click="goto(`${root}/${getUrlApp}/${getUrl}/${getCmd}/${es2.id};${es2.portal};${es2.nama}?next=${nextUrl}`)">
              <i class="fa fa-external-link"></i> <b>{{es1.kode}}.{{es2.kode}}</b> - {{es2.nama | uppercase}}
            </a>
          </li>
        </ul>

      </li>

    </ul>
  </li>
</template>

<script>

module.exports = {

  name: 'bs4-menu-portal',
  data: function() {
    return {
      data: [],
      toggleMenu: []
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
      default: 'Unit Kerja'
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
    }
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
          })
          .catch(e => console.log(e))
    },
    goto(url) {
      this.$window.location.href = url;
    }
  },
  created: function () {
    this.getData();
  },
  computed: {
    options: function () {
      if (this.data.length > 0) {
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