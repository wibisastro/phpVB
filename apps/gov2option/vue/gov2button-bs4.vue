<template>
    <b-button
            variant="primary"
            @click="toggleClick"
            :size="size"
            :block="block"
            :pill="pill"
            :squered="squared"
            :disabled="disabled"
            :class="{'pull-right': float==='right', 'pull-left': float==='left'}">
        <slot name="icon"></slot>
        {{ buttonLabel }}
    </b-button>
</template>

<script>

module.exports = {
    name: 'gov2button-bs4',
    props:  {
        buttonLabel: String,
        size: String,
        instance: {
            type: String,
            default: ''
        },
        float: {
            type: String,
            default: 'right'
        },
        block: {
            type: Boolean,
            default: false
        },
        pill: {
            type: Boolean,
            default: false
        },
        squared: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data: function () {
        return {
            isPressed: true
        }
    },
    methods: {
        toggleClick: function () {
            eventBus.$emit('toggleClick',this.isPressed);
            eventBus.$emit('onInstance', this.instance);
        },
        resetButton: function () {
            this.isPressed=true;
        }
    },
    created: function () {
        eventBus.$on('resetButton', this.resetButton);
    }
}
</script>