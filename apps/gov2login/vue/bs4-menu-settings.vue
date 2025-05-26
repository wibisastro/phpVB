<template>
    <li class="dropdown d-none d-md-block" :class="[status ? 'show' : '']">
        <a class="btn dropdown-toggle" data-toggle="dropdown" @click="toggle">
            Pengaturan
        </a>
        <ul class="dropdown-menu" :class="[status ? 'show' : '']">
            <li @click="toggleMenu[0].open=!toggleMenu[0].open">
                <a href="#" class="dropdown-toggle dropdown-nocaret">
                    <i class="fa fa-tasks"></i>
                    <span>Options</span>
                    <i class="drop-icon" :class="[toggleMenu[0].open ? 'fa fa-angle-down' : 'fa fa-angle-right']"></i>
                </a>
                <ul class="submenu" :class="[toggleMenu[0].open ? 'display-block' : 'display-none']" v-if="options.length > 0">
                    <li class="item" v-for="option in options">
                        <a :href="`${root}/${option.app}/options/view`">
                            <i :class="option.hasOwnProperty('icon') ? option.icon : 'fa fa-gears'"></i> {{option.app | uppercase}}
                        </a>
                    </li>
                </ul>
                <ul v-else class="submenu" :class="[toggleMenu[0].open ? 'display-block' : 'display-none']">
                    <li class="item">
                        <a href="#">
                            <i class="fa fa-info-circle"></i>
                            Belum ada options
                        </a>
                    </li>
                </ul>
            </li>

            <li @click="toggleMenu[1].open=!toggleMenu[1].open">
                <a href="#" class="dropdown-toggle dropdown-nocaret">
                    <i class="fa fa-code-fork"></i>
                    <span>Services</span>
                    <i class="drop-icon" :class="[toggleMenu[1].open ? 'fa fa-angle-down' : 'fa fa-angle-right']"></i>
                </a>
                <ul class="submenu" :class="[toggleMenu[1].open ? 'display-block' : 'display-none']" v-if="services.length > 0">
                    <li class="item" v-for="service in services">
                        <a :href="`${root}/${service.app}/services/view_services`">
                            <i :class="service.hasOwnProperty('icon') ? service.icon : 'fa fa-gears'"></i>
                          {{service.app | uppercase}}
                        </a>
                    </li>
                </ul>
                <ul v-else class="submenu" :class="[toggleMenu[1].open ? 'display-block' : 'display-none']">
                    <li class="item">
                        <a href="#">
                            <i class="fa fa-info-circle"></i>
                            Belum ada services
                        </a>
                    </li>
                </ul>

            </li>
        </ul>
    </li>
</template>

<script>

    module.exports = {

        name: 'bs4-menu-settings',
        data: function() {
            return {
                data: [],
                data_services: [],
                toggleMenu: [
                    {open: false},
                    {open: false},
                ]
            }
        },
        props: ['status','name', 'root'],
        methods: {
            toggle: function() {
                eventBus.$emit('togelMenu', this.name);
            },
            getData: function () {
                const url = `${this.root}/gov2option/index/getList`;
                axios.get(url)
                    .then(resp => {
                        this.data = resp.data.options;
                        this.data_services = resp.data.services;
                    })
                    .catch(e => console.log(e.message))
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
            services: function () {
              if (this.data_services.length > 0) {
                return this.data_services;
              } else {
                return []
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
</style>