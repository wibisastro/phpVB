<template>
    <div :id="id" :style="`width:${width}; height:${height};`"></div>
</template>
<script>
module.exports = {
    name: "highchart-bar",
    props: {
        chart: {
            type: Object,
            default: function() {
                return {
                    type: 'column'
                }
            }
        },
        width: {
            type: String,
            default: '100%'
        },
        height: {
            type: String,
            default: '400px'
        },
        title: {
            type: Object,
            default: function() {
                return {
                        // align:'center',
                        // floating:false,
                        // margin:0,
                        // style:{ "color": "#333333", "fontSize": "18px" },
                        // useHTML:false,
                        // verticalAlign:undefined,
                        // widthAdjust:0,
                        // x:0,
                        // y:undefined,
                        text: ''
                    }
            }
        },
        subtitle: {
            type: Object,
            default: function() {
                return {
                    // align:'center',
                    // floating:false,
                    // margin:0,
                    // style:{ "color": "#333333", "fontSize": "18px" },
                    // useHTML:false,
                    // verticalAlign:undefined,
                    // widthAdjust:0,
                    // x:0,
                    // y:undefined,
                    text: ''
                }
            }
        },
        xAxis: {
            type: Object,
            default: function() {
                return {
                    // categories: [
                    //     'Jan',
                    //     'Feb',
                    //     'Mar',
                    //     'Apr',
                    //     'May',
                    //     'Jun',
                    //     'Jul',
                    //     'Aug',
                    //     'Sep',
                    //     'Oct',
                    //     'Nov',
                    //     'Dec'
                    // ],
                    crosshair: true
                }
            }
        },
        yAxis: {
            type: Object,
            default: function() {
                return {
                    min: 0,
                    title: {
                        text: ''
                    }
                }
            }
        },
        tooltip: {
            type: Object,
            default: function() {
                return {
                    headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0"><b>{point.y:.0f} </b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                }
            }
        },
        plotOptions: {
            type: Object,
            default: function() {
                return {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0
                    }
                }
            }
        },
        series: {
            type: Array,
            default: function() {
                return [
                // {
                //     name: 'Tokyo',
                //     data: [49.9, 71.5, 106.4, 129.2, 144.0, 176.0, 135.6, 148.5, 216.4, 194.1, 95.6, 54.4]

                // }, {
                //     name: 'New York',
                //     data: [83.6, 78.8, 98.5, 93.4, 106.0, 84.5, 105.0, 104.3, 91.2, 83.5, 106.6, 92.3]

                // }, {
                //     name: 'London',
                //     data: [48.9, 38.8, 39.3, 41.4, 47.0, 48.3, 59.0, 59.6, 52.4, 65.2, 59.3, 51.2]

                // }, {
                //     name: 'Berlin',
                //     data: [42.4, 33.2, 34.5, 39.7, 52.6, 75.5, 57.4, 60.4, 47.6, 39.1, 46.8, 51.1]

                // }
                ]
            }
        },
        responsiveRules: {
            type: Array,
            default: function() {
                return [
                    {  
                        condition: {  
                            maxWidth: 500  
                            },  
                        chartOptions: {  
                            legend: {  
                                enabled: true,
                                align: 'center',
                                verticalAlign: 'bottom',
                                layout: 'horizontal' 
                            },
                        }  
                    }
                ]
            }
        },
        legend: {
            type: Object,
            default: function() {
                return {
                    // enabled: true,
                    // layout: 'vertical',
                    // align: 'right',
                    // verticalAlign: 'top',
                    // x: -40,
                    // y: 80,
                    // floating: true,
                    // borderWidth: 1,
                    // backgroundColor:Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
                    // shadow: true
                }
            }
        },
        colors: Array,
        loading: false
    },
    methods: {
        xAxisHandler(newValue) {
            if (this.chartInstance) {
                this.chartInstance.update({
                    xAxis: newValue
                }, true, true)
            }
        },
        yAxisHandler(newValue) {
            if (this.chartInstance) {
                this.chartInstance.update({
                    yAxis: newValue
                }, true, true)
            }
        },
        seriesHandler(newValue) {
            if (this.chartInstance) {
                this.chartInstance.update({
                    series: newValue
                }, true, true)
            }
        },
        colorHandler(newValue) {
            if (this.chartInstance) {
                if (Array.isArray(newValue)) {
                    this.chartInstance.update({
                        colors: newValue
                    }, true, true)
                }
            }
        },
        tooltipHandler(nv) {
            if (this.chartInstance) {
                this.chartInstance.update({
                    tooltip: nv
                }, true, true)
            }
        }
    },
    mounted() {
        const options = {
            chart: this.chart,
            title: this.title,
            subtitle: this.subtitle,
            xAxis: this.xAxis,
            yAxis: this.yAxis,
            tooltip: this.tooltip,
            plotOptions: this.plotOptions,
            series: this.series,
            responsive: {
                rules: this.responsiveRules  
            },
            lang: {
                noData: "Tidak ada data untuk ditampilkan"
            },
            noData: {
                style: {
                    fontWeight: 'bold',
                    fontSize: '15px',
                    color: '#303030'
                }
            },
            legend: this.legend
        };

        if (this.colors) {
            options.colors = this.colors;
        }
    
        this.chartInstance =  Highcharts.chart(this.id, options);
    },
    data() {
       return {
           id: Math.random().toString(16).slice(2),
           chartInstance: ''
       }
    },
    watch: {
        xAxis: {
            deep: true,
            handler: 'xAxisHandler'
        },
        yAxis: {
            deep: true,
            handler: 'yAxisHandler'
        },
        series: {
            deep: true,
            handler: 'seriesHandler'
        },
        colors: {
            deep: true,
            handler: 'colorHandler'
        },
        tooltip: {
            deep: true,
            handler: 'tooltipHandler'
        },
        loading(nv) {
            if (!nv && !this.chartInstance) {
                this.chartInstance =  Highcharts.chart(this.id, options);
            }
        }
    }
}
</script>
<style scoped>
.highcharts-figure, .highcharts-data-table table {
    min-width: 310px; 
    max-width: 800px;
    margin: 1em auto;
}

.highcharts-data-table table {
	font-family: Verdana, sans-serif;
	border-collapse: collapse;
	border: 1px solid #EBEBEB;
	margin: 10px auto;
	text-align: center;
	width: 100%;
	max-width: 500px;
}
.highcharts-data-table caption {
    padding: 1em 0;
    font-size: 1.2em;
    color: #555;
}
.highcharts-data-table th {
	font-weight: 600;
    padding: 0.5em;
}
.highcharts-data-table td, .highcharts-data-table th, .highcharts-data-table caption {
    padding: 0.5em;
}
.highcharts-data-table thead tr, .highcharts-data-table tr:nth-child(even) {
    background: #f8f8f8;
}
.highcharts-data-table tr:hover {
    background: #f1f7ff;
}
</style>
