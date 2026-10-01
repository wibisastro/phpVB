<template>
    <div class="p-3">
        <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>

        <div v-if="loading" class="text-body-secondary small mb-2">
            <span class="spinner-border spinner-border-sm me-1"></span>Memuat filter…
        </div>

        <div v-for="field in fields" :key="field.name" class="mb-3">
            <label class="form-label fw-semibold mb-1">{{ field.label || field.name }}</label>

            <input
                v-if="field.type === 'text'"
                type="text"
                class="form-control"
                :placeholder="field.placeholder || ''"
                v-model="nilai[field.name]"
            >

            <select
                v-else
                class="form-select js-gov2filter-select"
                :multiple="isMultiple(field)"
                :data-name="field.name"
                :data-placeholder="field.placeholder || ''"
                @change="onNativeChange(field, $event)"
            >
                <option v-if="!isMultiple(field)" value=""></option>
                <option v-for="opt in normalizedOptions(field)" :key="opt.id" :value="opt.id">{{ opt.text }}</option>
            </select>
        </div>

        <div v-if="!loading && !fields.length" class="text-body-secondary small">
            Belum ada field filter untuk tabel ini.
        </div>

        <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
            <button type="button" class="btn btn-outline-secondary" @click="reset" :disabled="loading || saving">
                Reset
            </button>
            <button type="button" class="btn btn-primary" @click="apply" :disabled="loading || saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>Terapkan
            </button>
        </div>
    </div>
</template>

<script>
module.exports = {
    name: 'gov2filter',
    data: function () {
        return {
            cfg: {url: '', instance: '', title: ''},
            fields: [],
            nilai: {},
            aktif: 0,
            loading: false,
            saving: false,
            error: ''
        };
    },
    methods: {
        endpoint: function (path) {
            var base = (this.cfg.url || '').replace(/\/+$/, '');
            if (!path) {
                return base;
            }
            if (/^https?:\/\//i.test(path) || path.charAt(0) === '/') {
                return path;
            }
            return base + '/' + path;
        },
        fieldByName: function (name) {
            return this.fields.find(function (field) { return field.name === name; }) || {};
        },
        isMultiple: function (field) {
            return !!field.multiple || field.type === 'select2-tags';
        },
        normalizedOptions: function (field) {
            var options = Array.isArray(field.options) ? field.options : [];
            return options.map(function (opt) {
                return {
                    id: opt.id !== undefined ? opt.id : opt.value,
                    text: opt.text !== undefined ? opt.text : (opt.nama !== undefined ? opt.nama : opt.id)
                };
            });
        },
        defaultValue: function (field) {
            return this.isMultiple(field) ? [] : '';
        },
        onOpen: function (event) {
            var cfg = (event && event.detail) ? event.detail : (window.__filterPanelCfg || {});
            this.cfg = {
                url: cfg.url || '',
                instance: cfg.instance || '',
                title: cfg.title || ''
            };
            this.loadFields();
        },
        loadFields: function () {
            var vm = this;
            if (!this.cfg.url) {
                this.fields = [];
                return;
            }

            this.loading = true;
            this.error = '';
            axios.get(this.endpoint('filterFields'), {params: {instance: this.cfg.instance}})
                .then(function (response) {
                    var data = response && response.data ? response.data : {};
                    var fields = Array.isArray(data) ? data : (data.fields || []);
                    // Vue 2: properti yang ditambahkan SETELAH objek dipasang ke data tidak reaktif —
                    // nilai dirakit utuh dulu, dan field disiapkan ber-`options` sebelum dipasang.
                    var nilai = {};
                    fields.forEach(function (field) {
                        nilai[field.name] = data.nilai && data.nilai[field.name] !== undefined
                            ? data.nilai[field.name]
                            : vm.defaultValue(field);
                        if (field.options_url && !Array.isArray(field.options)) {
                            field.options = [];
                        }
                    });
                    vm.fields = fields;
                    vm.nilai = nilai;
                    fields.forEach(function (field) {
                        if (field.options_url) {
                            vm.loadOptions(field);
                        }
                    });
                    vm.aktif = data.aktif || 0;
                    vm.$nextTick(function () {
                        vm.initSelect2();
                    });
                })
                .catch(function (err) {
                    vm.error = vm.errText(err);
                })
                .finally(function () {
                    vm.loading = false;
                });
        },
        loadOptions: function (field) {
            var vm = this;
            axios.get(this.endpoint(field.options_url))
                .then(function (response) {
                    var rows = response && response.data ? response.data : [];
                    if (!Array.isArray(rows) && rows.data && Array.isArray(rows.data)) {
                        rows = rows.data;
                    }
                    var options = rows.map(function (row) {
                        return {
                            id: row.id !== undefined ? row.id : row.value,
                            text: row.text !== undefined ? row.text : (row.nama !== undefined ? row.nama : row.id)
                        };
                    });
                    if (typeof vm.$set === 'function') {
                        vm.$set(field, 'options', options);  // Vue 2: pastikan render ulang
                    } else {
                        field.options = options;
                    }
                    vm.$nextTick(function () {
                        vm.initSelect2();
                    });
                })
                .catch(function (err) {
                    vm.error = vm.errText(err);
                });
        },
        initSelect2: function () {
            if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2)) {
                return;
            }

            var vm = this;
            var $ = window.jQuery;
            var $parent = $('#sidePanelOffcanvas');

            this.$el.querySelectorAll('select.js-gov2filter-select').forEach(function (el) {
                var $el = $(el);
                var field = vm.fieldByName(el.getAttribute('data-name'));
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.off('change.gov2filter').select2('destroy');
                }

                var options = {
                    width: '100%',
                    allowClear: true,
                    placeholder: field.placeholder || '',
                    dropdownParent: $parent
                };
                if (vm.isMultiple(field)) {
                    options.multiple = true;
                }

                $el.select2(options);
                // Select2 memicu change lewat jQuery .trigger(); listener native Vue tidak ikut jalan
                $el.off('change.gov2filter').on('change.gov2filter', function () {
                    vm.onNativeChange(field, {target: el});
                });
                vm.setControlValue(field, vm.nilai[field.name]);
            });
        },
        setControlValue: function (field, value) {
            var el = this.$el.querySelector('select[data-name="' + field.name + '"]');
            if (!el) {
                return;
            }

            var normalized = value === undefined || value === null ? this.defaultValue(field) : value;
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2
                && window.jQuery(el).hasClass('select2-hidden-accessible')) {
                window.jQuery(el).val(normalized).trigger('change.select2');
            } else if (this.isMultiple(field)) {
                var values = Array.isArray(normalized) ? normalized.map(String) : [];
                Array.from(el.options).forEach(function (opt) {
                    opt.selected = values.indexOf(String(opt.value)) !== -1;
                });
            } else {
                el.value = normalized;
            }
        },
        onNativeChange: function (field, event) {
            var el = event.target;
            var value = this.isMultiple(field)
                ? Array.from(el.selectedOptions).map(function (opt) { return opt.value; })
                : el.value;
            this.nilai[field.name] = value;
        },
        hitungAktif: function () {
            var total = 0;
            for (var key in this.nilai) {
                var value = this.nilai[key];
                if (Array.isArray(value) ? value.length : (value !== '' && value !== null && value !== undefined)) {
                    total++;
                }
            }
            return total;
        },
        apply: function () {
            var vm = this;
            this.saving = true;
            this.error = '';
            axios.post(this.endpoint(''), {
                cmd: 'filterApply',
                data: {
                    instance: this.cfg.instance,
                    nilai: this.nilai
                }
            })
                .then(function (response) {
                    var data = response && response.data ? response.data : {};
                    if (data.class === 'success') {
                        vm.aktif = data.aktif !== undefined ? data.aktif : vm.hitungAktif();
                        vm.dispatchApplied();
                        vm.notify(data);
                        vm.hidePanel();
                    } else {
                        vm.notify(data);
                    }
                })
                .catch(function (err) {
                    vm.notify(err.response && err.response.data ? err.response.data : {notification: vm.errText(err)});
                })
                .finally(function () {
                    vm.saving = false;
                });
        },
        reset: function () {
            var vm = this;
            this.saving = true;
            this.error = '';
            axios.post(this.endpoint(''), {
                cmd: 'filterReset',
                data: {
                    instance: this.cfg.instance
                }
            })
                .then(function (response) {
                    var data = response && response.data ? response.data : {};
                    vm.fields.forEach(function (field) {
                        vm.nilai[field.name] = vm.defaultValue(field);
                    });
                    vm.aktif = 0;
                    vm.dispatchApplied();
                    vm.notify(data);
                    vm.hidePanel();
                })
                .catch(function (err) {
                    vm.notify(err.response && err.response.data ? err.response.data : {notification: vm.errText(err)});
                })
                .finally(function () {
                    vm.saving = false;
                });
        },
        dispatchApplied: function () {
            window.dispatchEvent(new CustomEvent('gov2filter:applied', {
                detail: {
                    url: this.cfg.url,
                    instance: this.cfg.instance,
                    aktif: this.aktif
                }
            }));
        },
        notify: function (data) {
            if (window.eventBus && data && (data.notification || data.class)) {
                window.eventBus.$emit('openNotif', data);
            }
        },
        closeSelect2: function () {
            if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2)) {
                return;
            }
            window.jQuery('#sidePanel-filter select').each(function () {
                var $el = window.jQuery(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('close');
                }
            });
        },
        hidePanel: function () {
            this.closeSelect2();
            var panel = document.getElementById('sidePanelOffcanvas');
            if (panel && window.bootstrap && window.bootstrap.Offcanvas) {
                var offcanvas = window.bootstrap.Offcanvas.getInstance(panel);
                if (offcanvas) {
                    offcanvas.hide();
                }
            }
        },
        errText: function (err) {
            return err && err.response && err.response.data && err.response.data.notification
                ? err.response.data.notification
                : (err && err.message ? err.message : 'Permintaan gagal');
        }
    },
    created: function () {
        this._onOpen = this.onOpen.bind(this);
        window.addEventListener('gov2filter:open', this._onOpen);
    },
    mounted: function () {
        var panel = document.getElementById('sidePanelOffcanvas');
        if (panel) {
            this._onHide = this.closeSelect2.bind(this);
            panel.addEventListener('hide.bs.offcanvas', this._onHide);
        }
    },
    beforeUnmount: function () {
        window.removeEventListener('gov2filter:open', this._onOpen);
        var panel = document.getElementById('sidePanelOffcanvas');
        if (panel && this._onHide) {
            panel.removeEventListener('hide.bs.offcanvas', this._onHide);
        }
    },
    beforeDestroy: function () {
        this.beforeUnmount();
    }
};
</script>
