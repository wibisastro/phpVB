<template>
<div>
<progress class="progress" :class="setColor()" :value="lo" :max="hi">{{ percent }}%</progress> {{ percent }}%
</div>
</template>

<script>
module.exports = {
    name: 'gov2progress',
    props: {
        hi: Number,
        lo: Number
    },
    data() {
        return {
           percent: 100 
        }
    },
    methods: {
        setPercent: function() {
            this.percent=((this.lo/this.hi) * 100).toFixed(2);
        },
        setColor: function () {
            var color;
            if (this.percent<50) {color="is-danger";}
            else if (this.percent>=50 && this.percent<80) {color="is-warning";}
            else if (this.percent>=80 && this.lo<this.hi) {color="is-success";}
            else if (this.lo==this.hi) {color="is-info";}
            return color;
        }
    },
    created: function () { 
        this.setPercent();
    },
    watch: {
        hi: function () {
          this.setPercent();
        },
        lo: function () {
          this.setPercent();
        }
    }
}
</script>