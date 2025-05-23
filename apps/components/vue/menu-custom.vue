<template>
<div class="menu-custom" :class="{ none : isNone }">
    <div class="collapse navbar-collapse navbar-ex1-collapse" id="sidebar-nav" style="display:block;margin-top:10px;"> 
        <ul class="nav nav-pills nav-stacked" v-for="(mainmenu, idx) in mymenus">
            <li class="nav-header nav-header-first hidden-sm hidden-xs">
                {{ mainmenu.label.toUpperCase() }}
            </li>
            <template v-for="menus in mainmenu">
                <li v-for="(submenu, index) in menus" :class="[selected == idx+'-'+index ? open : '']" style="width:100%" v-if="submenu.caption">
                    <a :href="submenu.url" class="dropdown-nocaret" @click="choose(idx,index)" style="cursor:pointer;">
                        <template v-if="submenu.tab">
                            <template v-for="i in parseInt(submenu.tab)" >
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </template>
                        </template>
                        <i :class="submenu.icon" style="padding:6px;border-radius:2px;background-color:rgba(88,88,88,0.4);margin:auto;"></i>
                        <span>{{ submenu.caption }}</span>
                        <template v-if="submenu.menu">
                            &nbsp;<i :class="[selected == idx+'-'+index ? arrowicondown : arrowiconleft]"></i>
                        </template>
                    </a>
                    <ul :class="defaultClass" :style="[selected == idx+'-'+index ? activeObject : hiddenObject]" v-if="submenu.menu">
                        <template v-for="item in submenu.menu" v-if="Array.isArray(submenu.menu)">
                            <li>
                                <a :href="item.url">
                                    <template v-if="item.tab">
                                        <!-- &nbsp;&nbsp;<span class="vline"></span> -->
                                        <!-- <span style="font-size: 0.4rem;">
                                            <i class="fa fa-angle-double-right fa-xs"></i>
                                        </span> -->
                                        <template v-for="i in parseInt(item.tab)">
                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                        </template>
                                    </template>
                                    <i :class="item.icon"></i><span style="padding-left:10px;">{{ item.caption }}</span>
                                </a>
                            </li>
                        </template>
                        <template v-if="Array.isArray(submenu.menu) === false && submenu.menu !== null">
                          <li>
                            <a :href="submenu.menu.url">
                              <template v-if="submenu.menu.tab">
                                <template v-for="i in parseInt(submenu.menu.tab)">
                                  &nbsp;&nbsp;&nbsp;&nbsp;
                                </template>
                              </template>
                              <i :class="submenu.menu.icon"></i>
                              <span style="padding-left:10px;">{{ submenu.menu.caption }}</span>
                            </a>
                          </li>
                        </template>
                    </ul>
                </li>
            </template>
        </ul>
    </div>
</div>
</template>

<script>
	
module.exports = {
    name: 'menu-custom',
    data: function () {
        return {
            defaultClass: "submenu",
            isHidden: true,
            isActive: false,
            isShow: false,
            isNone: false,
            hiddenObject: {
                display: 'none',
            },
            activeObject: {
                display: 'block',
            },
            selected: null,
            open: 'open',
            mymenus: [],
            arrowiconleft: 'fa fa-angle-left drop-icon',
            arrowicondown: 'fa fa-angle-down drop-icon',
        }
    },
    props: {
        getUrl: String,
        root: String,
        profile: String,
    },
    methods: {
    	choose: function(idx,index) {
            if(this.selected == idx+'-'+index) {
                this.selected = -1;
                localStorage.pilih = -1;
            }
            else {
                this.selected = idx+'-'+index;
                localStorage.pilih = idx+'-'+index;    
            }
    	},
        toggleProfile: function() {
            this.isShow = !this.isShow;
        },
        loadMenu: function(data) {
            // console.log(data)
            this.mymenus = data;
            eventBus.$emit('emitMenu', data);
        },
        loadMenuFail: function(err) {
            console.log(err);
        },
        togelMobile: function() {
            this.isNone = !this.isNone;
        }
    },
    mounted() {
        // console.log(this.profile)
        if (localStorage.pilih) {
            this.selected = localStorage.pilih;
        }
        let url = '/'+this.getUrl+'/index/getMenus';
        // console.log(this.getUrl);
        axios.get(url)
            .then(response => this.loadMenu(response.data))
            .catch(error => this.loadMenuFail(error.response.data));

        var w = window.innerWidth;
        if(w < 980) {
            this.isNone = true;
        }
    },
    created() {
        eventBus.$on('togelMobile', this.togelMobile);
    }
}

</script>

<style>
    .vline {
        border-left: 1px dotted silver;
    }
    #sidebar-nav .nav > li > a > span {
        font-weight: 500 !important;
    }
</style>
