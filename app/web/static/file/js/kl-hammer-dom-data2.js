var HammerDomData = function (tplData) {
    var self = this;
    self.dataBinded = tplData;
    self.elesBinded = {};
    self.pathsBinded = [];
    self.bindData = function (opts) {
        var keysPath = opts.path;
        //eleDom, keysPath, dataType
        self.pathsBinded.push(opts);
        self._addDom(opts.dom, keysPath);
        if (typeof opts.dom.dataToValue === 'function') {
            opts.dom.dataToValue(self._getValByPath(keysPath));
        } else {
            if (opts.dom.advanceType === 'radioList') {
                opts.dom.setValue(self._getValByPath(keysPath));
            } else {
                if ((['radio', 'checkbox']).indexOf(opts.dom.type) === -1) {
                    opts.dom.value = self._getValByPath(keysPath);
                } else {
                    opts.dom.checked = self._getValByPath(keysPath);
                }
            }
        }
        opts.dom.changeFun = function () {
            //cli.log(this.value);
            var value = this.getDomValue();
            if (typeof opts.dom.valueToData === 'function') {
                value = this.valueToData(value);
            }
            self._setValByPath(opts.path, value);
            if (typeof opts.dom.changeCall === 'function') {
                opts.dom.changeCall(opts.dom);
            }
        };
        opts.dom.getDomValue = function () {
            return (['radio', 'checkbox']).indexOf(opts.dom.type) === -1 ? this.value : this.checked;
        };
        opts.dom.addEventListener('change', function () {
            console.log(this.value, this);
            opts.dom.changeFun();
            return false;
        });
        return self;
    };
    self._getValByPath = function (keysPath) {
        var keys = keysPath.split('.');
        var last = keys.splice(-1);
        return [self.dataBinded].concat(keys).reduce(function (a, b) {
            if (typeof a[b] === 'undefined') a[b] = {};
            return a[b]
        })[last] || '';
    };
    self._getObjValByPath = function (object, keysPath) {
        var keys = keysPath.split('.');
        var last = keys.splice(-1);
        return [object].concat(keys).reduce(function (a, b) {
            if (typeof a[b] === 'undefined') a[b] = {};
            return a[b]
        })[last] || '';
    };
    self._setValByPath = function (keysPath, value) {
        var keys = keysPath.split('.');
        var last = keys.splice(-1);
        [self.dataBinded].concat(keys).reduce(function (a, b) {
            if (typeof a[b] === 'undefined') a[b] = {};
            return a[b]
        })[last] = value;
        return self;
    };
    self._addDom = function (eleDom, keysPath) {
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
            self._setValByPath(opts.path, self._getObjValByPath(newDataBind, opts.path));
            if (typeof opts.dom.dataToValue === 'function') {
                opts.dom.dataToValue(self._getValByPath(opts.path));
            } else {
                opts.dom.value = self._getValByPath(opts.path);
            }
            opts.dom.addEventListener('change', function () {
                if (typeof opts.dom.valueToData === 'function') {
                    self._setValByPath(opts.path, this.valueToData(this.value));
                } else {
                    self._setValByPath(opts.path, this.valType === 'int' || this.type === 'number' ? parseInt(this.value) : this.value);
                }
            });
        });
        return self;
    };


    self.createRadioList = function (list) {
        var div = new Emt('div').setPros({advanceType: 'radioList', value: ''});
        div.radios = [];
        var unique = (new Date().getTime()).toString() + '_' + Math.random().toString();
        list.forEach(function (array, index) {
            var radio = new Emt('input').setPros({type: 'radio', value: array[1], name: unique});
            div.addNodes([new Emt('label').addNodes([radio, new Emt('span').setPros({textContent: array[0]})])]);
            radio.addEventListener('change', function () {
                div.value = radio.value;
                // div._setValByPath(div.path,  div.value );
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