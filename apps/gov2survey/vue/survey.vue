<template>
<div>
    <div class="row" v-if="survey.telah_mengisi">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Terima kasih, Anda telah menyelesaikan survey ini.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="row mb-3" v-if="survey.telah_mengisi"></div>

    <div class="card mb-3 p-4" v-if="survey._pertanyaan && survey._pertanyaan.length">
        <div class="row">
            <div class="col-12 clearfix">
                <div class="py-3 h5 text-center fw-bold">{{survey.kuesioner}}</div>
                <div class="py-3 h5 text-center" v-if="timeLeft">Berakhir dalam {{timeLeft}}</div>
            </div>
        </div>
        <hr>
        <div class="row">
            <template v-for="(chunk, index) in survey.pertanyaan">
                <div class="col-md-6 col-sm-12 bb-1" v-bind:key="index + 1">
                    <div class="question ms-2 ps-2 pt-2" v-for="(pertanyaan, pindex) in chunk" v-bind:key="pindex + 1">
                        <div class="py-2 h5 fw-bold">{{pertanyaan.nomor}}. {{pertanyaan.nama}}</div>

                        <div class="ms-3 ps-5 pt-0" v-for="(opsi, optIndex) in pertanyaan.opsi"
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

        <div class="row mb-3"></div>

        <div class="row" v-if="Object.keys(answers).length && !survey.telah_mengisi">
            <div class="col-12">
                <div class="progress" role="progressbar"
                     :aria-valuenow="Object.keys(answers).length"
                     aria-valuemin="0"
                     :aria-valuemax="survey._pertanyaan.length">
                    <div class="progress-bar bg-success"
                         :style="{width: (Object.keys(answers).length / survey._pertanyaan.length * 100) + '%'}">
                        {{Object.keys(answers).length}}/{{survey._pertanyaan.length}}
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center pt-3" v-if="survey._pertanyaan.length == Object.keys(answers).length && !survey.telah_mengisi">
            <div id="prev"> </div>
            <div class="ms-auto me-3"> <button class="btn btn-success" @click="confirmSimpan">Simpan</button> </div>
        </div>

        <div class="row mb-3"></div>
    </div>

    <div class="card mb-3 p-4 text-center" v-else>
        <h1><i class="bi bi-emoji-smile" aria-hidden="true"></i></h1><br>
        <span>Belum ada data survey</span>
    </div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="surveyConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit hasil survey</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 class="text-center">Apakah anda sudah mengisi survey dengan sejujurnya?</h6>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Belum yakin</button>
                    <button type="button" class="btn btn-primary" @click="simpan">Ya</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="surveySuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 p-2">
                    <h5 class="modal-title">Survey telah berhasil dikirim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Terimakasih telah meluangkan waktu anda untuk mengisi survey ini.
                </div>
                <div class="modal-footer border-top-0 p-2">
                    <button type="button" class="btn btn-sm btn-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
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
                    this.survey = res.data;
                    this.survey._pertanyaan = Array.from(res.data.pertanyaan);
                    if (this.survey.telah_mengisi) {
                        _.forEach(this.survey._pertanyaan, (pertanyaan) => {
                            this.answers[`${pertanyaan.survey_id}_${pertanyaan.id}`] =
                                pertanyaan.opsi[0].jawaban_id;
                        })
                    }
                    this.survey.pertanyaan = _.chunk(this.survey.pertanyaan, this.chunkSize)
                } else {
                    eventBus.$emit('openNotif', res.data);
                }
            })
            .catch(e => {
                eventBus.$emit('openNotif', e.response)
            })
        },
        confirmSimpan() {
            var modal = new bootstrap.Modal(document.getElementById('surveyConfirmModal'));
            modal.show();
        },
        simpan() {
            // Close confirm modal
            var confirmModal = bootstrap.Modal.getInstance(document.getElementById('surveyConfirmModal'));
            if (confirmModal) confirmModal.hide();

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

            this.payload = payload;

            axios.post(this.postUrl, payload)
                .then(res => {
                    if (res.data.class === 'success') {
                        var successModal = new bootstrap.Modal(document.getElementById('surveySuccessModal'));
                        successModal.show();
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
        disableSurvey() {
            this.survey.telah_mengisi = true;
            _.forEach(this.payload.answers, (answer) => {
                _.forEach(this.survey.pertanyaan, (chunk, chunk_index) => {
                    _.forEach(chunk, (pertanyaan, pert_index) => {
                        _.forEach(pertanyaan.opsi, (opsi, index) => {
                            this.survey.pertanyaan[chunk_index][pert_index].opsi[index].jawaban_id = answer.opsi_id
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
    unmounted(){
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
    background: var(--bs-primary, #5c90d2);
    transition: 300ms ease-in-out 0s
}

.options input[type="radio"]:checked~.checkmark:after {
    transform: translate(-50%, -50%) scale(1)
}

.bb-1 {
    border: 0;
    border-bottom: 1px solid rgba(0,0,0,.1);
}
</style>
