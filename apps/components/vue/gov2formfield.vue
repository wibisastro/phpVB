<template>
<div>
<article class="is-info" v-show="isOpen" id="myForm">
  <div class="message-header">
    Form
    <button class="delete" @click="isOpen=false"></button>
  </div>
  <div class="message-body">
      <form @submit.prevent="onSubmit" @keydown="form.errors.clear($event.target.name)">
        <div class="field is-horizontal" 
             v-for="(val, key) in fields" 
             v-if="val.name != 'cmd'">
          <div class="field-label is-normal">
              <label class="label" v-if="val.type != 'checkbox'">{{ val.label }}</label>
              <input class="input" type="checkbox" :placeholder="val.placeholder" v-model="form[val.name]" :disabled="val.disabled" v-if="val.type == 'checkbox'">
          </div>
          <div class="field-body">
            <div class="field">
              <div class="control has-icons-right">
                <input 
                       class="input" 
                       type="text" 
                       :placeholder="val.placeholder" 
                       v-model="form[val.name]" 
                       :disabled="val.disabled" 
                       v-if="!val.type">
                <div class="select" 
                     v-if="val.type == 'select' && !val.multiple">
                    <select 
                          v-model="form[val.name]" 
                          :disabled="val.disabled" @change="form.errors.clear($event.target.name)">
                    <option value="" disabled>Select dropdown</option>
                    <option 
                            :disabled="isDisabled[key2]" v-for="(val2, key2) in val.options" 
                            :value="key2">{{ val2 }}</option>
                    </select>
                </div>
                <b-field v-if="val.type == 'select' && val.multiple">
                        <b-select
                            multiple
                            native-size="8"
                            v-model="form[val.name]">
                            <option 
                            :disabled="isDisabled[key2]" v-for="(val2, key2) in val.options" 
                            :value="key2">{{ val2 }}</option>
                        </b-select>
                    </b-field>
                  <input 
                         class="input" 
                         type="password" 
                         :placeholder="val.placeholder" 
                         v-model="form[val.name]" 
                         :disabled="val.disabled" 
                         v-if="val.type == 'password'">
                  <gov2component v-model="form[val.name]"
                                 v-if="val.type == 'gov2component'" 
                                 :component-name="val.name"></gov2component>
                  <textarea :disabled="val.disabled" 
                            class="textarea" 
                            :placeholder="val.placeholder" 
                            v-model="form[val.name]"
                            v-if="val.type == 'textarea'"></textarea>
                  <label 
                         class="label" 
                         v-if="val.type == 'checkbox'">{{ val.label }}</label>
                    <span 
                          class="icon is-small is-right" 
                          v-if="val.required">
                      <i class="fa fa-warning"></i>
                    </span>
              </div>
              <p class="help is-danger" 
                 v-text="form.errors.get(val.name)" 
                 v-if="form.errors.has(val.name)"></p>
            </div>
          </div>
        </div>
        <div class="field is-horizontal">
          <div class="field-body">
            <div class="field is-grouped is-grouped-right">
              <div class="control">
                <button class="button is-primary" :disabled="form.errors.any()">
                  {{ submit }}
                </button>
              </div>
            </div>
          </div>
        </div>
    </form>
    </div>
</article>
  <div class="modal" :class="{ 'is-active': isConfirm }">
    <form @submit.prevent="onSubmit">
      <div class="modal-background" @click="isConfirm=false"></div>
      <div class="modal-content box">
          <span v-if="isDel">
            Data dengan nomor ID "{{ delData }}" akan dihapus
          </span>
          <span v-if="isHasChildren">
            Data ini tidak bisa dihapus karena memiliki sub-data sebanyak {{ childrenData }} baris.
          </span>
          <div class="field is-horizontal">
          <div class="field-body">
            <div class="field is-grouped is-grouped-right">
              <div class="control">
                <button class="button is-primary" @click="delProceed" v-if="isDel">
                  Delete
                </button>
                <a class="button is-light" @click="isConfirm=false">Cancel</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    <button class="modal-close" aria-label="close" @click="isConfirm=false"></button>
    </form>
    </div>
</div>
</template>

<script>
var gov2component=window.location.href+'/../vue/gov2component.vue';
module.exports = {
    name: 'formfield',
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
        }
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
            selected: []
        }
    },
    methods: {
        resetButton: function () {
            eventBus.$emit('resetButton');
            this.toggleForm();
        },
        scrollTo: function () {
          this.$scrollTo('#myForm', '500', this.options)  
        },
        toggleForm: function (theForm) {
            this.isOpen = theForm;
            this.form['cmd'] = 'add';
            this.submit = 'Add';
            this.form.reset();
        },
        onSubmit: function () {
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
            eventBus.$emit('refreshData',data['parent_id']);
            eventBus.$emit('openNotif',data);
            if (data['callback']) {this[data['callback']](data);}
        },
        formFail: function (data) {
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
            this.setLevel(1);
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
        }
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
        if (this.show) {
            this.isOpen=true;
        } 
    }
}
</script>

<style>

</style>