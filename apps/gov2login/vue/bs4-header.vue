<template>
	<div class="bs4-header">
		<header class="navbar navbar-expand-lg navbar-light container d-block d-lg-flex" id="header-navbar">    
		    <a class="navbar-brand float-left float-lg-none" href="/" id="logo" style="text-align:center">
		        <img style="width: 130px" alt="" src="https://sdi.kukarkab.go.id/sdi/css/logo.png"/>
		        <!-- <img alt="" class="normal-logo logo-black" src="../../../images/logo-bkn.png"/> -->
				<!-- <span class='philosopher'>KRISNA</span> -->
		    </a>
		    <button aria-controls="navbar-ex1-collapse" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler float-right float-lg-none" data-target=".navbar-ex1-collapse" data-toggle="collapse" type="button" @click="togelMobile">
			   <span class="fa fa-bars"></span>
		    </button>
		    <ul class="nav navbar-nav mr-auto d-none d-lg-block mrg-l-none">
		        <li class="bars">
		            <a class="btn" id="make-small-nav" @click="togelNav" style="padding-left:10px !important;padding-right:10px !important;padding-top:13px !important;">
		                <i class="fa fa-bars" style="border:1px solid #fff;padding:6px;border-radius:2px;">
		                </i>
		            </a>
		        </li>

          <template v-if="loadChild">
            <bs4-menu-settings
                :name="myTogels[5].name"
                :status="myTogels[5].status"
                :root="root"
            ></bs4-menu-settings>
          </template>

          <template v-if="loadChild">
            <bs4-menu-portal
                v-if="showPortalList"
                :name="myTogels[4].name"
                :status="myTogels[4].status"
                :root="root"
                :portal-name="portalName"
                get-url-app="sdi"
                get-url="index"
                get-portal-cmd="portal"
				:is-tagging-opd="isTaggingOpd"
                :change-portal-url="`${root}/sdi/index`" :resetable="true"
            ></bs4-menu-portal>
            <bs4-menu-portal v-else
                :name="myTogels[4].name"
                :status="myTogels[4].status"
                :root="root"
                :unit-name="unitName"
                :resetable="true"
				:is-tagging-opd="isTaggingOpd"
                 get-cmd="changeUnit"
                 caption="Filter Unit Kerja"
            ></bs4-menu-portal>
          </template>

		    </ul>
		    <ul class="nav navbar-nav ml-auto float-right float-lg-none" id="header-nav" v-if="accId > 0 || String(accId).length > 0">
		        <li class="dropdown profile-dropdown" :class="[myTogels[3].status ? 'show' : '']">
		            <a class="dropdown-toggle" data-toggle="dropdown" href="#" @click="togel('profileRight')">
		                <img alt="" src="../../../images/user.png"/>
		                <span class="d-none d-md-block">
		                    <strong>{{ fullname }} ({{ role.toUpperCase() }})</strong>
		                </span>
		                <b class="caret">
		                </b>
		            </a>
		            <ul class="dropdown-menu dropdown-menu-right" :class="[myTogels[3].status ? 'show' : '']">
		                <li>
		                    <a :href="root+'/'+myFolder+'/login'">
		                        <i class="fa fa-user">
		                        </i>
		                        Profile
		                    </a>
		                </li>
					<template v-if="role != 'guest'">
						<li class="dropdown-submenu" v-if="uMyRoles">
							<a href="#" class="dropdown-toggle"
							data-toggle="dropdown" role="button"
							aria-haspopup="true" aria-expanded="false" @click="csubmenu">
								<i class="fa fa-group"></i>
								<span class="nav-label"> Roles</span>
								<span class="caret"></span>
							</a>
						</li>
						<template v-if="ssubmenu">
							<li v-for="(i,idx) in uMyRoles" v-bind:key="i.id">
								<a :href="root+'/'+myFolder+'/role/'+idx">
								&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-angle-double-right"></i>{{ idx }}
								</a>
							</li>
						</template>
					</template>

		                <li>
		                    <a href="/gov2login.php?cmd=logout">
		                        <i class="fa fa-sign-out">
		                        </i>
		                        Logout
		                    </a>
		                </li>
		            </ul>
		        </li>
		    </ul>
			<ul class="nav navbar-nav ml-auto float-right float-lg-none" id="header-nav" v-else>
				<li v-show="false">
					<a :href="root+'/'+myFolder+'/login/auth/signup'">
						<span class="d-none d-md-block">
		                    Signup
		                </span>
					</a>
				</li>
				<li class="dropdown" :class="{show: myTogels[6].status}" @click="togel('login')">
					<!-- <a :href="root+'/'+myFolder+'/login'">
						<span class="d-none d-md-block">
		                    Login
		                </span>
					</a> -->

					<a class="btn dropdown-toggle">
		                Login
		            </a>

					<ul class="dropdown-menu dropdown-menu-right" :class="{show: myTogels[6].status}">
		                <li>
		                    <a href="#" style="pointer-events: none">
		                        <i class="fa fa-sign-in">
		                        </i>
		                        SSO BKN
		                    </a>
		                </li>
		                <li>
		                    <a :href="root+'/'+myFolder+'/login?type=gov2'">
		                        <i class="fa fa-sign-in">
		                        </i>
		                        SSO Gov 2.0
		                    </a>
		                </li>
		            </ul>
					
				</li>
			</ul>
		</header>
	</div>
</template>

<script>

var togels = [
	{ name: 'notif', status: false },
	{ name: 'message', status: false },
	{ name: 'item', status: false },
	{ name: 'profileRight', status: false },
  { name: 'portal', status: false },
  { name: 'settings', status: false },
  { name: 'login', status: false },
];

module.exports = {
	name: 'bs4-header',
	components: {
        'bs4-menu-notif': httpVueLoader('./bs4-menu-notif.vue'),
        'bs4-menu-message': httpVueLoader('./bs4-menu-message.vue'),
        'bs4-menu-item': httpVueLoader('./bs4-menu-item.vue'),
        'bs4-menu-portal': httpVueLoader('./bs4-menu-portal.vue'),
        'bs4-menu-settings': httpVueLoader('./bs4-menu-settings.vue'),
    },
	data: function() {
		return {
			myTogels: togels,
			myData0: [],
			myData1: [],
			myData2: [],
			loadChild: false,
			myFolder: '',
      ssubmenu: false,
      myRoles: [],
		}
	},
	props: ['getUrl','root','accId','fullname','domain','cmdId', 'role', 'portalName', 'unitName', 'isTaggingOpd'],
	methods: {
		togel: function(n) {
			for(let i in this.myTogels) {
				if(this.myTogels[i].name == n) {
					this.myTogels[i].status = !this.myTogels[i].status;
				}
				else {
					this.myTogels[i].status = false;
				}
			}
		},
		togelNav: function() {
			eventBus.$emit('togelParent',true);
		},
		togelMobile: function() {
			eventBus.$emit('togelMobile',true);
		},
		getData: function() {
			var link = this.getUrl.split('/');
			if(!link[1]) {
				link[1] = 'phlnlpk';
			}
			this.myFolder = link[1];
			let url = '/'+link[1]+'/index/getHeaders';
			// console.log(link);
	        axios.get(url)
	            .then(response => this.loadData(response.data))
	            .catch(error => this.loadDataFail(error.response.data));
		},
		loadData: function(data) {
			this.myData0 = data['header'][0];
			this.myData1 = data['header'][1];
			this.myData2 = data['header'][2];
			// console.log(this.myData2)
			this.loadChild = true;
		},
		loadDataFail: function(err) {

		},
    csubmenu: function() {
      this.ssubmenu = !this.ssubmenu
    },
    getPageroles: function() {
      var link = this.getUrl.split('/');
      if(!link[1]) {
        link[1] = 'renjakl';
      }
      let url = '/'+link[1]+'/index/getPageroles';
      axios.get(url)
          .then(response => this.loadRoles(response.data))
          .catch(function(error) {
            console.log('error get roles')
          });
    },
    loadRoles: function(data) {
      this.myRoles = data
    },
    getWidth: function() {
      // console.log(this.$refs["header-navbar"].clientWidth)
      if (this.wide) {
        var w = 1150
      }
      else {
        var w = 1000
      }
      eventBus.$emit('setTableWidth',w);
    },
	},
  computed: {
    uMyRoles: function() {
      return this.myRoles
    },
    showPortalList() {
      const forbidden = ['renobiro.bkn.kl2.web.id', 'reno.bkn.go.id', 'inspektorat.bkn.kl2.web.id',
	  					'inspektorat.bkn.go.id', 'keuanganbiro.bkn.kl2.web.id', 'hhkbiro.bkn.kl2.web.id'];

      return !forbidden.includes(this.$window.location.hostname);
    }
  },
	created() {
		eventBus.$on('togelMenu', this.togel);
		var vm = this;
		this.getData();
    this.getPageroles();
		// console.log('cmd: '+this.cmdId)

		window.addEventListener('click', function(e) {
			// close dropdown when clicked outside
			// try {
			// 	var li = e.target.offsetParent.classList;
			// 	var inc = li.contains('dropdown');
      //
			// 	if(!inc) {
			// 		for(let i in vm.myTogels) {
			// 			vm.myTogels[i].status = false;
			// 		}
			// 	}
			// }
			// catch {
			//
			// }
		})

    this.$nextTick(function() {
      window.addEventListener('resize', this.getWidth);
    })
	},
  mounted() {
    this.getWidth()
  }
}

</script>
