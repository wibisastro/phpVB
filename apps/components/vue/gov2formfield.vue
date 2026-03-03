<template>
<div>
<div class="card" v-show="isOpen" id="myForm">
  <div class="card-header d-flex justify-content-between align-items-center">
    Form
    <button type="button" class="btn-close" @click="isOpen=false"></button>
  </div>
  <div class="card-body">
      <form @submit.prevent="onSubmit" @keydown="form.errors.clear($event.target.name)">
        <div class="form-group row"
             v-for="(val, key) in fields"
             v-if="val.name != 'cmd'">
          <div class="col-auto">
              <label class="col-form-label fw-semibold" v-if="val.type != 'checkbox' && val.type != 'hidden'">{{ val.label }}</label>
              <input class="form-check-input" type="checkbox" :placeholder="val.placeholder" v-model="form[val.name]" :disabled="val.disabled" v-if="val.type == 'checkbox'">
          </div>
          <div class="col">
            <div>
              <input
                     class="form-control"
                     type="text"
                     :placeholder="val.placeholder"
                     v-model="form[val.name]"
                     :disabled="val.disabled"
                     v-if="!val.type">
              <select class="form-select"
                     v-if="val.type == 'select' && !val.multiple"
                     v-model="form[val.name]"
                     :disabled="val.disabled" @change="form.errors.clear($event.target.name)">
                  <option value="" disabled>Select dropdown</option>
                  <option
                          :disabled="isDisabled[key2]" v-for="(val2, key2) in val.options"
                          :value="key2">{{ val2 }}</option>
              </select>
              <select class="form-select"
                      multiple
                      size="8"
                      v-if="val.type == 'select' && val.multiple"
                      v-model="form[val.name]">
                  <option
                      :disabled="isDisabled[key2]" v-for="(val2, key2) in val.options"
                      :value="key2">{{ val2 }}</option>
              </select>
              <input
                     class="form-control"
                     type="password"
                     :placeholder="val.placeholder"
                     v-model="form[val.name]"
                     :disabled="val.disabled"
                     v-if="val.type == 'password'">
              <gov2component v-model="form[val.name]"
                             v-if="val.type == 'gov2component'"
                             :component-name="val.name"></gov2component>
              <textarea :disabled="val.disabled"
                        class="form-control"
                        :placeholder="val.placeholder"
                        v-model="form[val.name]"
                        v-if="val.type == 'textarea'"></textarea>
              <label
                     class="col-form-label fw-semibold"
                     v-if="val.type == 'checkbox'">{{ val.label }}</label>
              <span class="text-warning" v-if="val.required">
                  <i class="fa fa-warning"></i>
              </span>
            </div>
            <p class="text-danger small"
               v-text="form.errors.get(val.name)"
               v-if="form.errors.has(val.name)"></p>
          </div>
        </div>
        <div class="form-group row" v-if="!hideButton">
          <div class="col">
            <div class="d-flex justify-content-end gap-2">
              <button class="btn btn-primary" :disabled="form.errors.any()">
                {{ submit }}
              </button>
            </div>
          </div>
        </div>
    </form>
    </div>
</div>
  <div class="modal" :class="{ 'show d-block': isConfirm }">
    <div class="modal-dialog">
      <div class="modal-content">
        <form @submit.prevent="onSubmit">
          <div class="modal-body">
              <span v-if="isDel">
                Data dengan nomor ID "{{ delData }}" akan dihapus
              </span>
              <span v-if="isHasChildren">
                Data ini tidak bisa dihapus karena memiliki sub-data sebanyak {{ childrenData }} baris.
              </span>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary" @click="delProceed" v-if="isDel">Delete</button>
            <a class="btn btn-light" @click="isConfirm=false">Cancel</a>
          </div>
        </form>
      </div>
    </div>
    <div class="modal-backdrop fade show" @click="isConfirm=false"></div>
  </div>
</div>
</template>

<script>
var apppath = window.location.pathname; 
var n = apppath.search("gov2login/user");
if (n==1) {
    var gov2component=window.location.href+'/../../vue/gov2component.vue';
} else {
    var gov2component=window.location.href+'/../vue/gov2component.vue';    
}

module.exports = {
    name: 'formfield',
      components: {
        'gov2component': httpVueLoader(gov2component),
      },
    props: {
        fieldUrl: String,
        action: String,
        show: Boolean,
        defaultLevel:{
            type: Number,
            default: 1
        }, 
        parent_id: {
            type: Number,
            default: 0
        },
        instance: String,
        validation: Boolean,
        validationColumns: Array,
    },
    data: function () {
        return {
            isConfirm: false,
            isOpen: false,
            submit: 'Add',
            fields: [],
            delData: '',
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
            customButton:[],
            hideButton:false
        }
    },
    methods: {
        validate: function(data) {
          console.log(data);
        },
        resetButton: function () {
            eventBus.$emit('resetButton');
            this.toggleForm();
        },
        scrollTo: function () {
          this.$scrollTo('#myForm', '500', this.options)  
        },
        toggleForm: function (data) {
            this.isOpen = !this.isOpen;
            if (this.customButton.length==0) {
                this.form['cmd'] = 'add';
                this.submit = 'Add';   
            }
            this.form.reset();
        },
        onSubmit: function () { //console.log(this.action);
            this.form.submit('post',this.action)
                .then(data => this.formSuccess(data))
                .catch(error => this.formFail(error));
        },
        responseBox: function (data) {
            eventBus.$emit('responseBox',data);
        },
        refreshBrowser: function () {
            location.reload();  
        },
        formSuccess: function (data) {
            //console.log(data);
            eventBus.$emit('refreshData',data['parent_id']);
            eventBus.$emit('openNotif',data);
            if (data['callback']) {this[data['callback']](data);}
        },
        formFail: function (data) {
            console.log(data);
            eventBus.$emit('openNotif',data);
            if (data['callback']) {this[data['callback']]();}
        },
        formEdit: function (data) {
            this.form.reset();
            this.isOpen = true;
            for (let field in data) {
                this.form[field] = data[field];
            }
            this.form['cmd'] = 'update';
            this.submit = 'Update';
            this.scrollTo();
        },
        formConfirmDel: function (data) {
            this.isConfirm=true;
            this.delData=data;
            this.isDel=true;
            this.isHasChildren=false;
        },
        formConfirmHasChildren: function (data) {
            this.isConfirm=true;
            this.childrenData=data;
            this.isDel=false;
            this.isHasChildren=true;
        },
        delProceed: function () {
            this.form['id'] = this.delData;
            this.form['cmd'] = 'del';
        },
        confirmClose: function () {
            this.isConfirm=false;
        },
        setFields: function (data) {
            this.fields=Object.assign({}, data);
            this.setLevel(this.defaultLevel);
            if (this.show) {
                this.form['cmd'] = this.fields[0]['value'];
                this.submit = this.fields[0]['label'];
            }
            this.listenComponent();
        },
        setParentId: function (id) {
            if (id==-1) {
                this.form['parent_id']=this.parent_id;
            } else {
                this.form['parent_id'] = id;
            }
        },
        setLevel: function (level) {
            let data = this.fields;
            for (let field in data) {
                if (data[field]['name'] == 'level') {
                    for (let option in data[field]['options']) {
                        if (option == level || option == level+1) {
                            this.isDisabled[option]=false;
                            this.form['level'] = option;
                        } else {
                            this.isDisabled[option]=true;
                        }
                    }
                }
            }
        },
        listenComponent: function () {
            let data = this.fields;
            for (let field in data) {
                if (data[field]['type'] == 'gov2component') {
                    eventBus.$on('setField'+data[field]['name'], this.setField);
                }
            }
        },
        setField: function (data) {
            this.form[data['name']] = data['value'];
        },
        setButton: function (data) {
            this.customButton=data;
            this.form['cmd'] = data['cmd'];
            this.submit = data['caption'];
        },
        setHideButton: function () {
            this.hideButton=true;
        },
    },
    created: function () {
        eventBus.$on('toggleClick', this.toggleForm);
        eventBus.$on('dataEdit', this.formEdit);
        eventBus.$on('dataDel', this.formConfirmDel);
        eventBus.$on('hasChildren', this.formConfirmHasChildren);
        axios.get(this.fieldUrl)
            .then(response => this.setFields(response.data))
            .catch(error => console.log(error.response.data));
        eventBus.$on('refreshPath', this.setParentId);
        eventBus.$on('setLevel', this.setLevel);
        eventBus.$on('toggleForm', this.toggleForm);
        eventBus.$on('setButton', this.setButton);
        eventBus.$on('hideButton', this.setHideButton);
        if (this.show) {
            this.isOpen=true;
        } 
    }
}
</script>

<style>

</style>