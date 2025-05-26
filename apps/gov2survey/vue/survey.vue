<template>
<div>
    <div class="row" v-if="survey.telah_mengisi">
        <div class="col-12">
            <b-alert show dismissible fade variant="success">Terima kasih, Anda telah menyelesaikan survey ini.</b-alert>
        </div>
    </div>

    <div class="row" v-if="survey.telah_mengisi"><div class="col-12">&nbsp;</div></div>
    
    <div class="container mb-2" v-if="survey._pertanyaan && survey._pertanyaan.length">
        <div class="row">
            <div class="col-12 clearfix">
                <div class="py-3 h5 text-center"><b>{{survey.kuesioner}}</b></div>
                <div class="py-3 h5 text-center" v-if="timeLeft">Berakhir dalam {{timeLeft}}</div>
            </div>
        </div>
        <hr>
        <div class="row">
            <template v-for="(chunk, index) in survey.pertanyaan">
                <div class="col-md-6 col-sm-12 bb-1" v-bind:key="index + 1">
                    <div class="question ml-sm-2 pl-sm-2 pt-2" v-for="(pertanyaan, pindex) in chunk" v-bind:key="pindex + 1">
                        <div class="py-2 h5"><b>{{pertanyaan.nomor}}. {{pertanyaan.nama}}</b></div>

                        <div class="ml-md-3 ml-sm-3 pl-md-5 pt-sm-0 pt-3" v-for="(opsi, optIndex) in pertanyaan.opsi"
                            id="options" v-bind:key="optIndex + 1"> 
                            <label class="options">{{opsi.nomor}}. {{opsi.nama}} 
                                <input type="radio" 
                                    :name="`_${opsi.survey_id}${opsi.pertanyaan_id}`" 
                                    v-model="answers[`${opsi.survey_id}_${opsi.pertanyaan_id}`]" 
                                    :value="opsi.id"
                                    :disabled="opsi.jawaban_id && survey.telah_mengisi"> 
                                <span class="checkmark"></span> 
                            </label>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="row"><div class="col-12">&nbsp;</div></div>

        <div class="row" v-if="Object.keys(answers).length && !survey.telah_mengisi">
            <div class="col-12">
                <b-progress  :max="survey._pertanyaan.length" variant="success">
                    <b-progress-bar :value="Object.keys(answers).length">
                        {{Object.keys(answers).length}}/{{survey._pertanyaan.length}}
                    </b-progress-bar>
                </b-progress>
            </div>
        </div>

        <div class="d-flex align-items-center pt-3" v-if="survey._pertanyaan.length == Object.keys(answers).length && !survey.telah_mengisi">
            <div id="prev"> </div>
            <div class="ml-auto mr-sm-5"> <button class="btn btn-success" @click="confirmSimpan">Simpan</button> </div>
        </div>

        <div class="row"><div class="col-12">&nbsp;</div></div>
    </div>

    <div class="container mb-2 text-center" v-else>
        <h1><i class="fa fa-smile-o" aria-hidden="true"></i></h1><br>
        <span>Belum ada data survey</span>
    </div>
</div>
    
</template>

<script>
module.exports = {
    name: 'survey',
    props: {
        getUrl: {
            type: String,
            default: 'survey/table/-1'
        },
        postUrl: {
            type: String,
            default: 'survey'
        },
        chunkSize: {
            type: Number,
            default: 5
        }
    },
    methods: {
        getData() {
            axios.get(this.getUrl)
            .then(res => {
                if (res.hasOwnProperty('data') && res.data.hasOwnProperty('class') === false) {
                    this.$set(this, 'survey', res.data);
                    this.survey._pertanyaan = Array.from(res.data.pertanyaan);
                    if (this.survey.telah_mengisi) {
                        _.forEach(this.survey._pertanyaan, (pertanyaan) => {
                            this.$set(this.answers, `${pertanyaan.survey_id}_${pertanyaan.id}`, 
                                pertanyaan.opsi[0].jawaban_id);
                        })
                    }
                    this.survey.pertanyaan = _.chunk(this.survey.pertanyaan, this.chunkSize)
                    // this.timeLeft = new Date(this.survey.date_end).getTime();
                } else {
                    eventBus.$emit('openNotif', res.data);
                }
            })
            .catch(e => {
                eventBus.$emit('openNotif', e.response)
            })
        },
        confirmSimpan() {
            const el = this.$createElement;
            const message = el('div', {domProps: {class: 'row'}}, [
                el('h6',{class: 'text-center'},
                    'Apakah anda sudah mengisi survey dengan sejujurnya ?')
            ]);
            this.$bvModal.msgBoxConfirm(message, {
                title: 'Submit hasil survey',
                centered: true,
                size: 'md',
                okVariant: 'primary',
                okTitle: 'Ya',
                cancelTitle: 'Belum yakin'
            })
                .then(ok => {
                    if (ok) {
                        this.simpan();
                    }
                })
                .catch(e => console.log(e));
        },
        simpan() {
            const payload = {
                cmd: 'simpan',
                answers: []
            };
            _.forEach(Object.keys(this.answers), (key) => {
                const pertanyaan = _.filter(this.survey._pertanyaan, (pertanyaan) => {
                    return pertanyaan.id === key.split('_')[1]
                })[0]

                const opsi = _.filter(pertanyaan.opsi, (opsi) => {
                    return opsi.id === this.answers[key]
                })[0]

                let answer = {
                    app: opsi.app,
                    kuesioner_unit_id: opsi.kuesioner_unit_id,
                    survey_id: opsi.survey_id,
                    pertanyaan_id: opsi.pertanyaan_id,
                    opsi_id: opsi.id,
                    bobot: opsi.bobot,
                    nomor: opsi.nomor
                }

                payload.answers.push(answer);
            })

            this.$set(this, 'payload', payload);

            axios.post(this.postUrl, payload)
                .then(res => {
                    if (res.data.class === 'success') {
                        this.showMsgBoxTwo();
                        this.disableSurvey()
                    } else {
                        eventBus.$emit(res.data);
                    }
                })
                .catch(e => {
                    const payload = {
                        class: 'danger',
                        callback: 'infoSnackbar',
                        notification: e.response.message
                    }
                    eventBus.$emit('openNotif', payload);
                    console.log(e)
                })
        },
        showMsgBoxTwo() {
            this.boxTwo = ''
            this.$bvModal.msgBoxOk('Terimakasih telah meluangkan waktu anda untuk mengisi survey ini.', {
            title: 'Survey telah berhasil dikirim',
            size: 'sm',
            buttonSize: 'sm',
            okVariant: 'success',
            headerClass: 'p-2 border-bottom-0',
            footerClass: 'p-2 border-top-0',
            centered: true
            })
            .then(ok => {
                // this.disableSurvey()
            })
            .catch(err => {
                
            })
        },
        disableSurvey() {
            this.survey.telah_mengisi = true;
            _.forEach(this.payload.answers, (answer) => {
                _.forEach(this.survey.pertanyaan, (chunk, chunk_index) => {
                    _.forEach(chunk, (pertanyaan, pert_index) => {
                        _.forEach(pertanyaan.opsi, (opsi, index) => {
                            this.$set(this.survey.pertanyaan[chunk_index][pert_index].opsi[index], 'jawaban_id',  answer.opsi_id)
                        })
                    })
                })
            })
        },
        countDown(value) {
            var eventTime = new Date(value.date_end).getTime();
            var currentTime = Date.now();
            var diffTime = eventTime - currentTime;
            var duration = moment.duration(diffTime, 'milliseconds');
            var interval = 1000;
            vm = this;

            vm.interval = setInterval(function(){
                duration = moment.duration(duration - interval, 'milliseconds');
                const days = duration.days();
                if (days) {
                    vm.timeLeft = duration.days() + ' hari ' + duration.hours() + ":" + duration.minutes() + ":" + duration.seconds();
                } else {
                    vm.timeLeft = duration.hours() + ":" + duration.minutes() + ":" + duration.seconds();
                }
            }, interval);
        }
    },
    created() {
        this.getData();
    },
    mounted() {
        
    },
    destroyed(){
        clearInterval(this.interval);
    },
    data() {
        return {
            survey: {
                _pertanyaan: [],
                telah_mengisi: false
            },
            answers: {},
            payload: {
                cmd: 'simpan',
                answers:[]
            },
            timeLeft : '',
            interval: 0
        }
    },
    watch: {
        survey: {
            deep: true,
            handler: 'countDown'
        }
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box
}

body {
    background-color: #333
}

.container {
    background-color: #fff;
    color: #555;
    border-radius: 5px;
    padding: 20px;
    font-family: 'Montserrat', sans-serif;
}

.options {
    position: relative;
    padding-left: 40px
}

#options label {
    display: block;
    margin-bottom: 15px;
    font-size: 16px;
    cursor: pointer
}

.options input {
    opacity: 0
}

.checkmark {
    position: absolute;
    top: -1px;
    left: 0;
    height: 20px;
    width: 20px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 50%
}

.options input:checked~.checkmark:after {
    display: block
}

.options .checkmark:after {
    content: "";
    width: 10px;
    height: 10px;
    display: block;
    background: white;
    position: absolute;
    top: 50%;
    left: 50%;
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(0);
    transition: 300ms ease-in-out 0s
}

.options input[type="radio"]:checked~.checkmark {
    /* background: #21bf73; */
    background: #5c90d2;
    transition: 300ms ease-in-out 0s
}

.options input[type="radio"]:checked~.checkmark:after {
    transform: translate(-50%, -50%) scale(1)
}

.btn-primary {
    background-color: #555;
    color: #ddd;
    border: 1px solid #ddd
}

.btn-primary:hover {
    background-color: #21bf73;
    border: 1px solid #21bf73
}

.btn-success {
    padding: 5px 25px;
    background-color: #21bf73
}

@media(max-width:576px) {
    .question {
        width: 100%;
        word-spacing: 2px
    }
}

hr {
    /* border-top: 2px solid #555 !important; */
    border-radius: 3px;
}

.progress {
    height: 1rem;
    line-height: 0;
    font-size: .75rem;
    background-color: #e9ecef;
    border-radius: .25rem !important;
}

.bb-1 {
    border: 0;
    border-bottom: 1px solid rgba(0,0,0,.1);
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert {
    position: relative;
    padding: .75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: .25rem;
}
</style>
