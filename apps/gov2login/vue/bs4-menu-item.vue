<template>
	<li class="dropdown d-none d-md-block" :class="[status ? 'show' : '']">
        <a class="btn dropdown-toggle" data-toggle="dropdown" @click="childTogel">
            {{ d.caption }}
        </a>
        <ul class="dropdown-menu" :class="[status ? 'show' : '']">
            <li class="item" v-for="i in myItems">
                <a :href="root+i.uri">
                    <i :class="i.icon">
                    </i>
                    {{ i.caption }}
                </a>
            </li>
        </ul>
    </li>
</template>

<script>

module.exports = {

	name: 'bs4-menu-item',
    data: function() {
        return {
            myItems: this.d.items,
        }
    },
	props: ['status','name','d','root'],
	methods: {
		childTogel: function() {
			eventBus.$emit('togelMenu', this.name);
		}
	},

}

</script>