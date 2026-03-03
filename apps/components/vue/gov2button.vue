<template>
<div class="control">
    <a class="btn btn-primary d-block" @click="toggleClick" :class="{ 'btn-warning': isPressed, 'btn-lg': buttonSize=='large' }" >
        <span class="icon">
          <i :class="{'fa fa-minus': isPressed}" class="fa fa-plus"></i>
        </span>
        <span>{{ buttonLabel }}</span>
    </a>
</div>
</template>

<script>

module.exports = {
    name: 'gov2button',
    props:  {
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
            eventBus.$emit('toggleClick',this.isPressed);
        },
        resetButton: function () {
            this.isPressed=false;
        }
    },
    created: function () {
        eventBus.$on('resetButton', this.resetButton);
    }
}
</script>