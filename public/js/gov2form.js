/*
Taken from VUE tutorial and modified by Wibisono Sastrodiwiryo <wibi@alumni.ui.ac.id>
*/
class Errors {
    constructor() {
        this.errors = {};
    }
    
    get(field) {
        if (this.errors[field]) {
            return this.errors[field];
        }
    }
    
    record(errors) {
        this.errors = errors;
    }
    
    clear(field) {
        if (field) {
            delete this.errors[field];
        } else {
            this.errors={}
        }
    }
    
    has(field) {
        return this.errors.hasOwnProperty(field);
    }
    
    any() {
        return Object.keys(this.errors).length > 0;
    }
}

class Form {
    constructor(data) {
        this.originalData=data;
        for (let field in data) {
            if (data[field]['value']) {
                this[data[field]['name']] = data[field]['value'];    
            } else {
                if (data[field]['multiple']) {
                    this[data[field]['name']] = [];
                } else {
                    this[data[field]['name']] = '';
                }
            }
            
        }
        this.errors = new Errors();
    }
    
    data() {
        let data = Object.assign({}, this);
        delete data.originalData;
        delete data.errors;
        return data;
    }
    
    reset() {
        let reset = this.data();
        for (let field in reset) {
            if (field != 'cmd' && field != 'parent_id') {
                this[field] = '';
            }
        }
        this.errors.clear();
    }
    
    submit(requestType, url) {
        return new Promise((resolve, reject)=>{
            axios[requestType](url, this.data())
            .then(response => {
                this.onSuccess(response.data);
                resolve(response.data);
            })
            .catch(error => {
                this.onFail(error.response.data);
                reject(error.response.data);
            });
        });
    }
    
    onSuccess(data) {
        this.reset();
    }
    
    onFail(errors) {
        this.errors.record(errors);
    }
}