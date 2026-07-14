<template>
<a class="btn btn-primary d-block" @click="toggleClick" :class="{ 'btn-warning': isPressed, 'btn-lg': buttonSize=='large' }">
    <i :class="{'fa fa-minus': isPressed}" class="fa fa-plus"></i>
    <span>{{ buttonLabel }}</span>
</a>
</template>

<script>
// Port Vue 3 dari apps/components/vue/gov2button.vue (#6118 3b).
import eventBus from '../eventBus.js'

export default {
    name: 'gov2button',
    props: {
        buttonLabel: String,
        buttonSize: String
    },
    data: function () {
        return {
            isPressed: false
        }
    },
    methods: {
        toggleClick: function () {
            this.isPressed = !this.isPressed;
            eventBus.$emit('toggleClick', this.isPressed);
        },
        resetButton: function () {
            this.isPressed = false;
        }
    },
    created: function () {
        eventBus.$on('resetButton', this.resetButton);
    }
}
</script>
