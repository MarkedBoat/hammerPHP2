/**
 *
 * @constructor
 * @requires dialog
 */
var ApiParamsControl = function () {
    let self = this;
    self.params = [];
    self.current_param_ele = {};
    self.dom = new Emt('div').setPros({className: 'api_params_div'});
    self.dialog = new HammerDialog();


    self.vals_div = new Emt('div').setPros({className: 'api_params_vals_div'});
    self.vals_list_div = new Emt('div').setPros({className: 'api_params_vals_list_div'});
    self.vals_list_div_btn_add = new Emt('button').setPros({textContent: '新增备选值', className: 'api_params_add_val_btn'});

    self.dialog.addContentDom(self.vals_div.addNodes([
        self.vals_list_div,
        self.vals_list_div_btn_add
    ]));
    self.vals_list_div_btn_add.addEventListener('click', function () {
        var val_ele = new ApiParamEleVal(self.current_param_ele);
        self.vals_list_div.addNodes([val_ele.dom]);
    });

    self.params_div = new Emt('div').setPros({className: 'api_params_params_div'});
    self.new_param_btn = new Emt('button').setPros({textContent: '新增', className: 'api_params_new_param_btn'});
    self.new_param_by_json_obj_btn = new Emt('button').setPros({
        textContent: '由json object 增加对象',
        className: 'api_params_new_param_by_json_obj_btn',
        dialog: false
    });

    self.dom.addNodes([
        self.params_div,
        self.new_param_btn,
        self.new_param_by_json_obj_btn
    ]);

    self.new_param_btn.addEventListener('click', function () {
        var new_ele = new ApiParamEle(self);
        self.params_div.addNodes([new_ele.dom]);
        self.current_param_ele = new_ele;
    });

    self.new_param_by_json_obj_btn.addEventListener('click', function () {
        if (self.new_param_by_json_obj_btn.dialog === false) {
            self.new_param_by_json_obj_btn.dialog = new HammerDialog();
            self.new_param_by_json_obj_btn.input_or_out = new Emt('textarea').setPros({className: 'new_params_by_json_obj_textarea'});
            self.new_param_by_json_obj_btn.btn_in = new Emt('button').setPros({textContent: '输入新值'});
            self.new_param_by_json_obj_btn.btn_out = new Emt('button').setPros({textContent: '导出已有值'});

            self.new_param_by_json_obj_btn.data_type = new Emt('select').setPros({className: 'hammer_api_params_data_type'});
            self.new_param_by_json_obj_btn.data_type.appendChild(new Option('json key value', 'json_kv'));
            self.new_param_by_json_obj_btn.data_type.appendChild(new Option('json list', 'json_list'));
            self.new_param_by_json_obj_btn.data_type.appendChild(new Option('url', 'url'));
            self.new_param_by_json_obj_btn.data_type.appendChild(new Option('postman', 'postman'));

            self.new_param_by_json_obj_btn.dialog.addContentDom(self.new_param_by_json_obj_btn.input_or_out);
            self.new_param_by_json_obj_btn.dialog.addContentDom(self.new_param_by_json_obj_btn.data_type);
            self.new_param_by_json_obj_btn.dialog.addContentDom(self.new_param_by_json_obj_btn.btn_in);
            self.new_param_by_json_obj_btn.dialog.addContentDom(self.new_param_by_json_obj_btn.btn_out);
        }
        self.new_param_by_json_obj_btn.dialog.showDialog();
        self.new_param_by_json_obj_btn.btn_in.addEventListener('click', function () {
            try {
                var ar_tmp = [];

                switch (self.new_param_by_json_obj_btn.data_type.value) {
                    case "json_kv":
                        var obj = JSON.parse(self.new_param_by_json_obj_btn.input_or_out.value);
                        for (var i in obj) {
                            ar_tmp.push({k: i, v: obj[i], fun: '', vals: [], brief: ''});
                        }
                        break;
                    case 'postman':
                        var tmp_ar = self.new_param_by_json_obj_btn.input_or_out.value.split("\n");
                        tmp_ar.forEach(function (tmp_str) {
                            var tmp_index = tmp_str.indexOf(':');
                            if (tmp_index !== -1) {
                                var tmp_k = tmp_str.substr(0, tmp_index);
                                var tmp_v = tmp_str.substr(tmp_index + 1);
                                ar_tmp.push({k: tmp_k, v: tmp_v, fun: '', vals: [], brief: ''});
                            }
                        });
                        break;
                    case 'url':
                        var tmp_ar = self.new_param_by_json_obj_btn.input_or_out.value.split("&");
                        tmp_ar.forEach(function (tmp_str) {
                            var tmp_index = tmp_str.indexOf('=');
                            if (tmp_index !== -1) {
                                var tmp_k = tmp_str.substr(0, tmp_index);
                                var tmp_v = tmp_str.substr(tmp_index + 1).urldecode();
                                ar_tmp.push({k: tmp_k, v: tmp_v, fun: '', vals: [], brief: ''});
                            }
                        });
                        break;
                    default:
                        alert('导出不支持:' + self.new_param_by_json_obj_btn.data_type.value);
                        break;
                }
                var obj_tmp={};
                self.params_div.childNodes.forEach(function (childNode) {
                    obj_tmp[childNode.obj.data.k]={dom:childNode.obj,isdiff:false};
                    //cli.log(childNode.obj.data);
                });
                console.log(obj_tmp);
                ar_tmp.forEach(function (opt) {
                    if(typeof obj_tmp[opt.k]==='undefined'){
                        var div_tmp = new ApiParamEle(self, false);
                        div_tmp.loadApiParamEleData(opt);//{k: opt.k, v: opt.v, fun: '', vals: [], brief: ''}
                        //cli.log(div_tmp, div_tmp.dom);
                        self.params_div.addNodes([div_tmp.dom]);
                    }else{
                        obj_tmp[opt.k].dom.input_select.checked =  true;
                        obj_tmp[opt.k].dom.input_k.value = opt.k;
                        obj_tmp[opt.k].dom.input_v.value = opt.v;
                        obj_tmp[opt.k].dom.input_v.name = opt.k;
                        obj_tmp[opt.k].dom.input_fun.value = opt.fun;
                        obj_tmp[opt.k].dom.input_brief.value = opt.brief;
                        obj_tmp[opt.k].isdiff=true;
                    }
                });
                for (var i in obj_tmp) {
                    if (obj_tmp[i].isdiff === false && obj_tmp[i].dom.input_k.value.substr(0, 4) !== 'adv_') {
                        console.log(obj_tmp[i].dom);
                        obj_tmp[i].dom.dom.remove();
                    }
                }
                self.afterChangeInput();
                self.new_param_by_json_obj_btn.dialog.hideDialog();
            } catch (e) {
                console.log(e);
                alert('错误');
            }
        });
        self.new_param_by_json_obj_btn.btn_out.addEventListener('click', function () {
            try {
                var list = self.getParamList();
                var tmp_obj = {};
                var tmp_ar = [];
                var tmp_ar2 = [];
                list.forEach(function (info) {
                    tmp_obj[info.k] = info.v;
                    tmp_ar2.push(info.k + '=' + info.v.urlencode());
                    tmp_ar.push(info.k + ':' + info.v);
                });
                switch (self.new_param_by_json_obj_btn.data_type.value) {
                    case "json_kv":
                        self.new_param_by_json_obj_btn.input_or_out.value = JSON.stringify(tmp_obj);
                        break;
                    case 'postman':
                        self.new_param_by_json_obj_btn.input_or_out.value = tmp_ar.join("\n");
                        break;
                    case 'url':
                        self.new_param_by_json_obj_btn.input_or_out.value = tmp_ar2.join('&');
                        break;
                    default:
                        alert('导出不支持:' + self.new_param_by_json_obj_btn.data_type.value);
                        break;
                }
            } catch (e) {
                console.log(e);
                alert('错误');
            }
        });
    });

    self.loadParamList = function (array) {
        array.forEach(function (opt) {
            var div_tmp = new ApiParamEle(self, false);
            div_tmp.loadApiParamEleData(opt);//{k: opt.k, v: opt.v, fun: '', vals: [], brief: ''}
            console.log(div_tmp, div_tmp.dom);
            self.params_div.addNodes([div_tmp.dom]);
        });
        self.afterChangeInput();

    };

    /**
     * 通知本类 有了变化  input性质
     */
    self.afterChangeInput = function () {
        self.params = [];
        self.params_div.childNodes.forEach(function (childNode) {
            self.params.push(childNode.obj.data);
            //cli.log(childNode.obj.data);
        });
        //cli.log(self.params);
        self.afterChangeOut();
    };
    self.clearAll = function () {
        var objs = [];
        self.params_div.childNodes.forEach(function (childNode, index) {
            objs.push(childNode.obj);

        });
        objs.forEach(function (obj) {
            obj.dom.remove();
        })
    };
    /**
     * 通知别处 有变化了  out性质
     */
    self.afterChangeOut = function () {

    };
    self.getParamList = function () {
        return self.params;
    };
    self.getUrlString = function () {
        var ar = [];
        self.params.forEach(function (obj) {
            if (obj.select) {
                ar.push(obj.k + '=' + obj.v);
            }

        });
        return ar.join('&');
    };

    return self;
};
var ApiParamEleVal = function (root) {
    let self = this;
    self.root = root;
    self.vals = [];
    self.dom = new Emt('div').setPros({className: 'api_param_val_div', obj: self});
    self.input_k = new Emt('input').setPros({type: 'text', className: 'api_param_input_key'});
    self.input_v = new Emt('input').setPros({type: 'text', className: 'api_param_input_val'});
    self.btn_del = new Emt('button').setPros({textContent: 'del', className: 'api_param_btn_more'});
    self.dom.addNodes([
        self.input_k,
        self.input_v,
        self.btn_del,
    ]);
    self.loadData = function (opt) {
        self.input_k.value = opt.k;
        self.input_v.value = opt.v;
    };
    self.getData = function () {
        return {
            input_k: self.input_k.value,
            input_v: self.input_v.value,
        }
    };
    self.btn_del.addEventListener('click', function () {
        self.dom.remove();
    });
    return self;
};


/**
 * 参数列表中的一栏参数 选项
 * @param root
 * @param call_after_change
 * @returns {ApiParamEle}
 * @constructor
 */
var ApiParamEle = function (root, call_after_change) {
    let self = this;
    self.root = root;
    self.data = {};
    self.vals = [];
    self.dom = new Emt('div').setPros({className: 'api_param_div', obj: self});
    self.input_select = new Emt('input').setPros({type: 'checkbox', className: 'api_param_input_select'});
    self.input_k = new Emt('input').setPros({type: 'text', className: 'api_param_input_key',});
    self.input_v = new Emt('textarea').setPros({type: 'text', className: 'api_param_input_val',});
    self.input_fun = new Emt('select').setPros({className: 'api_param_input_fun',});
    self.input_brief = new Emt('input').setPros({type: 'text', className: 'api_param_input_brief',});
    self.btn_more = new Emt('button').setPros({textContent: 'more', className: 'api_param_btn_more'});
    self.btn_del = new Emt('button').setPros({textContent: 'del', className: 'api_param_btn_del'});
    self.dom.addNodes([
        self.input_select,
        self.input_k,
        self.input_v,
        self.input_fun,
        self.btn_more,
        self.input_brief,
        self.btn_del
    ]);
    [{t: '签名', v: 'sign'}].forEach(function (opt_fun) {
        self.input_fun.addNodes([new Emt('option').setPros({textContent: opt_fun.t, value: opt_fun.v})])
    });
    self.loadApiParamEleData = function (opt) {
        self.input_select.checked = opt.select || true;
        self.input_k.value = opt.k;
        self.input_v.value = opt.v;
        self.input_v.name = opt.k;
        self.input_fun.value = opt.fun;
        self.input_brief.value = opt.brief;
        opt.vals.forEach(function (val) {
            self.input_vals.addNodes([new Emt('option').setPros({select: true, textContent: val.t + ':' + val.v, value: val.v})])
        });
        self.getApiParamEleData();
    };
    self.getApiParamEleData = function () {
        var t = {
            k: self.input_k.value,
            v: self.input_v.value,
            fun: self.input_fun.value,
            brief: self.input_brief.value,
            select: self.input_select.checked,
            vals: [],
        };
        self.data = t;
        return t;
    };

    self.afterApiParamEleChange = function () {
        self.getApiParamEleData();
        if (typeof self.root.afterChangeInput === 'function') {
            self.root.afterChangeInput();
        }
    };
    [self.input_select, self.input_k, self.input_v, self.input_fun, self.input_brief,].forEach(function (input) {
        input.addEventListener('change', function () {
            if (self.input_select.checked) {
                self.input_v.name = self.input_k.value;
            } else {
                self.input_v.name = '';
            }

            self.afterApiParamEleChange();
        });
    });
    //input_vals: self.input_vals.value,
    self.btn_more.addEventListener('click', function () {
        self.root.dialog.showDialog();
    });
    self.btn_del.addEventListener('click', function () {
        self.dom.remove();
        self.afterApiParamEleChange();
    });
    if (typeof call_after_change === 'undefined' || call_after_change !== false) {
        self.afterApiParamEleChange();
    } else {
        self.getApiParamEleData();
    }
    return self;
};
