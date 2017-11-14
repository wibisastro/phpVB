<template>
     <li :class="{ 'is-active': isActive }">
     <slot><a :href="url" >{{ caption }}</a></slot>
     </li>
</template>

<script>
module.exports = {
    name: 'breadcrumb',
    props: {
        url: {
            type: String,
            default: '/'
        },
        caption: {
            type: String,
            default: 'Menu Item'
        },
        isActive: {
            type: String,
            default: false
        },  
    },
}
</script>

<style>

</style>