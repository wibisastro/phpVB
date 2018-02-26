<template>
     <li><slot><a :href="url" :class="{ 'is-active': isActive }">{{ caption }}</a></slot></li>
</template>

<script>
module.exports = {
    name: 'menu-list',
    props: {
        url: {
            type: String,
            default: '/',
            required: true
        },
        caption: {
            type: String,
            default: 'Menu Item'
        },
        isActive: {
            type: String,
            default: false
        }  
    },
    data: function() {
        return {
            menuList: '<a>Default</a>',
        }
    }
}
</script>

<style>

</style>