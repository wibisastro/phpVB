<template>
<div>
    <b-modal id="modal-tall" title="Form" v-model="isOpen" hide-footer>
    <div class="row" id="myForm">
        <div class="col-lg-12">
            <div class="main-box">
                <div class="form-group"></div>
                <div class="main-box-body clearfix">
                    <form @submit.prevent="onSubmit" @keydown="form.errors.clear($event.target.name)">

                        <div class="form-group"
                             :class="{'form-group-select2': val.type === 'select', 'has-error': form.errors.has(val.name)}"
                             v-for="(val, key) in fields"
                             v-if="val.name !== 'cmd'">

                            <label :for="val.name" v-if="val.type != 'hidden'">
                                {{val.label}}
                                <span class="red" v-if="val.required">*</span>
                            </label>
                            
                            <!-- field type hidden -->
                            <input :id="val.name" type="hidden" class="form-control"
                                   v-model="form[val.name]"
                                   v-if="val.type === 'hidden'"
                                   v-numeric="val.hasOwnProperty('directive') && val.directive === 'numeric'"
                                   :data-test="`input-${val.name}`">

                            <!-- field type checkbox -->
                            <input :id="val.name" type="checkbox"
                                   :placeholder="val.placeholder"
                                   v-model="form[val.name]"
                                   :disabled="val.disabled"
                                   v-if="val.type === 'checkbox'"
                                   :data-test="`input-${val.name}`">

                            <!-- field type text -->
                            <input :id="val.name" type="text" class="form-control"
                                   :placeholder="val.placeholder"
                                   v-model="form[val.name]"
                                   :disabled="val.disabled"
                                   v-if="!val.type"
                                   v-numeric="val.hasOwnProperty('directive') && val.directive === 'numeric'"
                                   :data-test="`input-${val.name}`">

                            <!-- field type date -->
                            <input :id="val.name" type="date" class="form-control"
                                   :placeholder="val.placeholder"
                                   v-model="form[val.name]"
                                   :disabled="val.disabled"
                                   v-if="val.type === 'date'"
                                   v-numeric="val.hasOwnProperty('directive') && val.directive === 'numeric'"
                                   :data-test="`input-${val.name}`">
                                   
                            <!-- field type password -->
                            <input :id="val.name" type="password" class="form-control"
                                   :placeholder="val.placeholder"
                                   v-model="form[val.name]"
                                   :disabled="val.disabled"
                                   v-if="val.type === 'password '"
                                   :data-test="`input-${val.name}`">

                            <!-- field type select -->
                            <select class="form-control"
                                    :id="val.name"
                                    :multiple="val.type === 'select' && val.multiple"
                                    v-model="form[val.name]"
                                    :disabled="val.disabled"
                                    @change="form.errors.clear($event.target.name)"
                                    v-if="val.type === 'select'"
                                    :data-test="`input-${val.name}`">
                                <option disabled v-if="!val.multiple">Select dropdown</option>
                                <option :disabled="isDisabled[key2]"
                                        v-for="(val2, key2) in val.options"
                                        :value="key2">{{val2}}</option>
                            </select>
                            
                            <gov2component v-model="form[val.name]"
                                 v-if="val.type == 'gov2component'" 
                                 :component-name="val.name"></gov2component>

                            <textarea class="form-control"
                                      :disabled="val.disabled"
                                      :placeholder="val.placeholder"
                                      v-model="form[val.name]"
                                      v-if="val.type === 'textarea'"
                                      :data-test="`input-${val.name}`">
                            </textarea>

                            <!-- form field help-block -->
                            <span class="help-block"
                                  v-text="form.errors.get(val.name)"
                                  v-if="form.errors.has(val.name)">
                            </span>
                        </div>

                        <div class="form-group">
                            <hr>
                            <div class="pull-right">
                                <button class="btn btn-primary"
                                        :disabled="form.errors.any()"
                                        data-test="button-form-submit">
                                    {{ customButton.hasOwnProperty('caption') ? customButton.caption : submit }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </b-modal>
    <div class="md-modal md-effect-1" id="modal-1" :class="{'md-show': isConfirm}">
        <div class="md-content">
            <div class="modal-body">
                <span v-if="isSoftDel">Data dengan nomor ID "{{ delData }}" akan dihapus</span>
                <span v-if="isDel">Data dengan nomor ID "{{ delData }}" akan dihapus</span>
                <span v-if="isHasChildren">Data ini tidak dapat dihapus karena memiliki sub-data sebanyak {{childrenData}} baris.</span>
            </div>
            <div class="modal-footer">
                <form @submit.prevent="onSubmit">
                    <button type="submit" class="btn btn-danger" @click="softDelProceed" v-if="isSoftDel">Delete</button>
                    <button type="submit" class="btn btn-danger" @click="delProceed" v-if="isDel">Delete</button>
                    <button class="btn btn-secondary" @click="isConfirm=false">Cancel</button>
                </form>
            </div>
        </div>
    </div>
    <div class="md-overlay"></div>
</div>
</template>

<script>
var gov2component=window.location.href+'/../../vue/gov2component.vue';

module.exports  = {
    name: "gov2formfield-bs4",
    components: {
        'gov2component': httpVueLoader(gov2component),
    },
    props: {
        fieldUrl: String,
        action: String,
        show: Boolean,
        parent_id: {
            type: Number,
            default: 0
        },
        multipleForm: {
            type: Boolean,
            default: false
        }
    },

    data: function () {
        return {
            isConfirm: false,
            isOpen: false,
            submit: 'Simpan',
            fields: [],
            delData: '',
            isSoftDel: false,
            isDel: true,
            isHasChildren: false,
            isDisabled: [],
            form: new Form(this.fields),
            scrollOptions: {
                easing: 'ease-in',
                x: false,
                y: true
            },
            selected: [],
            onInstance: '',
            customButton:[]
        }
    },

    methods: {
        resetButton: function () {
            eventBus.$emit('resetButton');
            this.toggleForm();
        },
        scrollTo: function () {
            // this.$scrollTo('#myForm', '500', this.options);
        },
        toggleForm: function (form) {
            this.isOpen = form;
            this.form['cmd'] = 'add';
            this.submit = 'Simpan';
            this.form.reset();
        },
        onSubmit: function () {
            let url = this.action;
            if (this.multipleForm && this.onInstance)
            {
                url = `instance_${this.onInstance}`;
            }
            this.form.submit('post', url)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
        responseBox: function (data) {
            eventBus.$emit('responseBox', data);
        },
        refreshBrowser: function () {
            location.reload();
        },
        formSuccess: function (data) {
            this.isOpen = false;
            eventBus.$emit(`refreshData${this.onInstance}`, data['parent_id']);
            eventBus.$emit('openNotif', data);

            if (data['callback']) {this[data['callback']]()}
        },
        formFail: function (data) {
            eventBus.$emit('openNotif', data);

            if (data['callback']) {this[data['callback']]()}
        },
        formEdit: function (fields) {
            this.form.reset();
            this.isOpen = true;

            for (let field in fields)
            {
                this.form[field] = fields[field];
            }

            this.form['cmd'] = 'update';
            this.submit = 'Simpan';
            this.scrollTo();
        },
        formConfirmSoftDel: function (data) {
            this.isConfirm = true;
            this.delData = data;
            this.isSoftDel = true;
            this.isDel = false;
            this.isHasChildren = false;
        },
        formConfirmDel: function (data) {
            this.isConfirm = true;
            this.delData = data;
            this.isSoftDel = false;
            this.isDel = true;
            this.isHasChildren = false;
        },
        formConfirmHasChildren: function (data) {
            this.isConfirm = true;
            this.childrenData = data;
            this.isSoftDel = false;
            this.isDel = false;
            this.isHasChildren = true;
        },
        softDelProceed: function () {
            this.form['id'] = this.delData;
            this.form['cmd'] = 'softDel';
            this.isConfirm = false;
        },
        delProceed: function () {
            this.form['id'] = this.delData;
            this.form['cmd'] = 'del';
        },
        confirmClose: function () {
            this.isConfirm = false;
        },
        setFields: function (data) {
            this.fields = Object.assign({}, data);
            this.setLevel(1);

            if (this.show)
            {
                this.form['cmd'] = this.fields[0]['value'];
                this.submit = this.fields[0]['label'];
            }
            this.listenComponent();
        },
        setParentId: function (id) {
            if (id === -1)
            {
                this.form['parent_id'] = this.parent_id;
            } else {
                this.form['parent_id'] = id;
            }
        },
        setLevel: function (level) {
            let data = this.fields;

            for (let field in data)
            {
                if (data[field]['name'] == 'level')
                {
                    for (let option in data[field]['options'])
                    {
                        if (option == level || option == level+1)
                        {
                            this.isDisabled[option] = false;
                            this.form['level'] = option;
                        } else {
                            this.isDisabled[option] = true;
                        }
                    }
                }
            }
        },
        listenComponent: function () {
            let data = this.fields;

            for (let field in data)
            {
                if (data[field]['type'] === 'gov2component')
                {
                    eventBus.$on('setField' + data[field]['name'], this.setField);
                }
            }
        },
        setField: function (data) {
            this.form[data['name']] = data['value'];
        },
        setActiveInstance: function (instance) {
            this.onInstance = instance;
        },
        setChain: function (value) {
            let field = null;
            Object.keys(this.fields).forEach(obj => {
                const _field = this.fields[obj];

                if (_field.hasOwnProperty('chained_to'))
                {
                    if (_field.chained_to === value.name)
                    {
                        field = _field;
                    }
                }
            });

            if (field)
            {
                this.form[field['name']] = value[field['chained_to_on_field']];
            }
        },
        setButton: function (data) {
            this.customButton=data;
            this.form['cmd'] = data['cmd'];
            this.submit = data['caption'];
        },
    },
    created: function () {
        eventBus.$on('toggleClick', this.toggleForm);
        eventBus.$on('coyOpenForm', this.toggleForm);
        eventBus.$on('dataEdit', this.formEdit);
        eventBus.$on('dataDel', this.formConfirmDel);
        eventBus.$on('dataSoftDel', this.formConfirmSoftDel);
        eventBus.$on('hasChildren', this.formConfirmHasChildren);
        eventBus.$on('onInstance', this.setActiveInstance);

        axios.get(this.fieldUrl)
            .then(response => this.setFields(response.data))
            .catch(error => console.log(error.response.data));

        eventBus.$on('refreshPath', this.setParentId);
        eventBus.$on('setLevel', this.setLevel);
        eventBus.$on('toggleForm', this.toggleForm);
        eventBus.$on('setButton', this.setButton);

        if (this.show)
        {
            this.isOpen = false;
        }

        eventBus.$on('set_chain', this.setChain)
    },
    directives: {
        numeric: {
            update: function (el, binding) {
                if ((!binding.hasOwnProperty('value')) || binding.value)
                {
                    if (el.value.length > 0)
                    {
                        el.value = el.value.replace(/[a-zA-Z\D]+/i, '');
                    }
                }
            }
        }
    },
    watch: {
        onInstance: function () {
            if (this.multipleForm)
            {
                axios.get(`${this.fieldUrl}/${this.onInstance}`)
                    .then(response => this.setFields(response.data))
                    .catch(error => eventBus.$emit('openNotif', error.response.data));
            }
        },
        form: function () {
            if (this.form.hasOwnProperty('errors'))
            {
                this.form.errors.clear()

            } else {

            }
        },
        customButton: function () {
            if (this.customButton.hasOwnProperty('cmd'))
            {
                this.form.cmd = this.customButton.cmd
            }
        }
    }
}
</script>

<style scoped>

form > .form-group > label,
form > .form-group > input,
form > .form-group > select,
form > .form-group > textarea  {
    font-size: 14px !important;
}
    .required {
        color: red;
    }

</style>