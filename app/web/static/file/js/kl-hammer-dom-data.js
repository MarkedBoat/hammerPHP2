var HammerDomData = function (tplData) {
    var self = this;
    self.dataBinded = tplData;
    self.elesBinded = {};
    self.pathsBinded = [];
    self.bindData = function (opts) {
        var keysPath = opts.path;
        //eleDom, keysPath, dataType
        self.pathsBinded.push(opts);
        self.bindEleByPath(opts.ele, keysPath);
        if (typeof opts.ele.dataToValue === 'function') {
            opts.ele.dataToValue(self.getValByPath(keysPath));
        } else {
            if (opts.ele.advanceType === 'radioList') {
                opts.ele.setValue(self.getValByPath(keysPath));
            } else {
                if ((['radio', 'checkbox']).indexOf(opts.ele.type) === -1) {
                    opts.ele.value = self.getValByPath(keysPath);
                } else {
                    opts.ele.checked = self.getValByPath(keysPath);
                }
            }

        }
        opts.ele.changeFun=function(){
            //cli.log(this.value);
            var value = (['radio', 'checkbox']).indexOf(opts.ele.type) === -1 ? this.value : this.checked;
            if (typeof opts.ele.valueToData === 'function') {
                self.setValByPath(opts.path, this.valueToData(value));
            } else {
                self.setValByPath(opts.path, value);
            }
            if (typeof opts.ele.changeCall === 'function') {
                opts.ele.changeCall(opts.ele);
            }
        };
        opts.ele.addEventListener('change', function () {
            console.log(this.value,this);
            opts.ele.changeFun();
            return false;
            console.log(this.value);
            var value = (['radio', 'checkbox']).indexOf(opts.ele.type) === -1 ? this.value : this.checked;
            if (typeof opts.ele.valueToData === 'function') {
                self.setValByPath(opts.path, this.valueToData(value));
            } else {
                self.setValByPath(opts.path, value);
            }
            if (typeof opts.ele.changeCall === 'function') {
                opts.ele.changeCall(opts.ele);
            }
        });
        return self;
    };
    self.getValByPath = function (keysPath) {
        var keys = keysPath.split('.');
        var last = keys.splice(-1);
        return [self.dataBinded].concat(keys).reduce(function (a, b) {
            if (typeof a[b] === 'undefined') a[b] = {};
            return a[b]
        })[last] || '';
    };
    self.getObjValByPath = function (object, keysPath) {
        var keys = keysPath.split('.');
        var last = keys.splice(-1);
        return [object].concat(keys).reduce(function (a, b) {
            if (typeof a[b] === 'undefined') a[b] = {};
            return a[b]
        })[last] || '';
    };
    self.setValByPath = function (keysPath, value) {
        var keys = keysPath.split('.');
        var last = keys.splice(-1);
        [self.dataBinded].concat(keys).reduce(function (a, b) {
            if (typeof a[b] === 'undefined') a[b] = {};
            return a[b]
        })[last] = value;
        return self;
    };
    self.bindEleByPath = function (eleDom, keysPath) {
        var keys = keysPath.split('.');
        var last = keys.splice(-1);
        [self.elesBinded].concat(keys).reduce(function (a, b) {
            a[b] = {};
            return a[b]
        })[last] = eleDom;
        return self;
    };

    self.reloadBindDataByPath = function (newDataBind) {
        self.pathsBinded.forEach(function (opts, index) {
            self.setValByPath(opts.path, self.getObjValByPath(newDataBind, opts.path));
            if (typeof opts.ele.dataToValue === 'function') {
                opts.ele.dataToValue(self.getValByPath(opts.path));
            } else {
                opts.ele.value = self.getValByPath(opts.path);
            }
            opts.ele.addEventListener('change', function () {
                if (typeof opts.ele.valueToData === 'function') {
                    self.setValByPath(opts.path, this.valueToData(this.value));
                } else {
                    self.setValByPath(opts.path, this.valType === 'int' || this.type === 'number' ? parseInt(this.value) : this.value);
                }
            });
        });
        return self;
    };
    self.createRadioList = function (list) {
        var div = new Emt('div').setPros({advanceType: 'radioList', value: ''});
        div.radios = [];
        var unique=(new Date().getTime()).toString()+'_'+Math.random().toString();
        list.forEach(function (array,index) {
            var radio = new Emt('input').setPros({type: 'radio', value: array[1],name:unique});
            div.addNodes([new Emt('label').addNodes([radio, new Emt('span').setPros({textContent: array[0]})])]);
            radio.addEventListener('change', function () {
                div.value = radio.value;
               // div.setValByPath(div.path,  div.value );
               // cli.log('change');
              //  div.changeFun();  div.change 事件居然有效，闻所未闻，先用着，不用从这调了
            });
            div.radios.push(radio);
        });
        div.setValue = function (val) {
            div.radios.forEach(function (radio) {
                if (radio.value === val) {
                    radio.click();
                }
            });
        };
        return div;
    };
    return self;
};