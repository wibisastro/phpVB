<template>
<div>
    <div class="progress">
        <div class="progress-bar" role="progressbar" :class="setColor()" :style="{ width: percent + '%' }" :aria-valuenow="percent" aria-valuemin="0" aria-valuemax="100">{{ percent }}%</div>
    </div>
    {{ percent }}%
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
            if (this.percent<50) {color="bg-danger";}
            else if (this.percent>=50 && this.percent<80) {color="bg-warning";}
            else if (this.percent>=80 && this.lo<this.hi) {color="bg-success";}
            else if (this.lo==this.hi) {color="bg-info";}
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
