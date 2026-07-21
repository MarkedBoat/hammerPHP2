var HammerLogicRoot = function () {

    var self = this;
    self.data = {};

    self.convBack = function (opts_input) {

    };
    self.setData = function (opts) {
        self.data = new HammerLogicDataNode(opts);
    };
    self.dom = new HammerLogicDomNode();
    self.loadData = function () {
        self.dom.loadData(self.data);
    };
    self.dom.classList.add('logic_root');

    self.dom.btn_remove.parentNode.remove();
    //self.dom.input_desc.remove();
    //self.dom.btn_debug.parentNode.remove();
    self.getData = function () {
        var tree = self.dom.getTree();
        console.log(tree);
        return tree;
    };

    self._getData = function (data) {

    };
    self.getConvPhpData = function (treeData, obj) {
        if (typeof obj === 'undefined') {
            var obj = {};
        }

        if (treeData.debug) {
            if (treeData.type === 'node') {
                obj[treeData.name] = {};
                treeData.nodes.forEach(function (subNode) {
                    self.getConvPhpData(subNode, obj[treeData.name]);
                })
            } else {
                console.log(treeData.val_type, treeData.val);
                var tmp_val = treeData.val;
                switch (treeData.val_type) {
                    case 'obj':
                        tmp_val = JSON.parse(treeData.val);
                        break;
                    case 'int':
                        tmp_val = parseInt(treeData.val);
                        break;
                }
                obj[treeData.name] = tmp_val;
            }
        }
        return obj;
    };


    /**
     * 重写这个方法可以获取树data
     * @param data
     */
    self.treeDataCallback = function (data) {
        var tree_map = self.getData();
        console.log(tree_map);
    };
    var btn_getData = new Emt('button').setPros({textContent: '获取数据'});
    btn_getData.addEventListener('click', function () {
        self.treeDataCallback(self.getData());
    });
    self.dom.btns.addNodes([btn_getData]);
    return self;
};

var HammerLogicDataNode = function (opts) {
    var self = this;
    if (typeof opts.name !== 'string') {
        console.log(opts);
        throw new Error('name');
    }

    if (typeof opts.debug !== 'boolean') {
        console.log(opts);
        throw new Error('debug');
    }
    if (typeof opts.desc !== 'string') {
        console.log(opts);
        throw new Error('desc');
    }
    if (typeof opts.type === 'undefined') {
        opts.type = 'node';
    }
    if (typeof opts.val === 'undefined') {
        opts.val = '';
    }
    if (typeof opts.val_type === 'undefined') {
        opts.val_type = '';
    }
    if (typeof opts.nodes === 'undefined' || typeof opts.nodes.forEach === 'undefined') {
        opts.nodes = []
    }
    self.name = opts.name;
    self.debug = opts.debug;
    self.desc = opts.desc;
    self.type = opts.type;
    self.val = opts.val;
    self.val_type = opts.val_type;

    /*
    switch (self.val_type) {
        case 'obj':
            self.val = JSON.stringify(self.val, null, 2);
            break;
        default :
            self.val = self.val.toString();
            break;
    }*/


    self.nodes = [];
    opts.nodes.forEach(function (opts2) {
        self.nodes.push(new HammerLogicDataNode(opts2));
    });
    return self;
};

var HammerLogicDomNode = function () {
    var self = new Emt('div').setPros({className: 'hammer_logic_node'});
    self.type = 'node';
    self.btn_debug = new Emt('input').setPros({type: 'checkbox', className: 'hammer_input_logic_node_debug'});
    self.btn_add = new Emt('button').setPros({textContent: 'add', className: 'hammer_input_logic_node_add'});
    self.btn_add_val = new Emt('button').setPros({textContent: 'add value', className: 'hammer_input_logic_node_add_val'});
    self.btn_remove = new Emt('button').setPros({textContent: 'remove', className: 'hammer_input_logic_node_remove'});


    self.input_name = new Emt('input').setPros({type: 'text', className: 'hammer_input_logic_node_name', placeholder: '逻辑节点name,如方法名'});
    self.input_desc = new Emt('textarea').setPros({className: 'hammer_input_logic_node_desc', placeholder: '节点描述'});
    self.input_val = new Emt('textarea').setPros({className: 'hammer_input_logic_node_val', placeholder: '假数据'});
    self.input_val_type = new Emt('input').setPros({type: 'text', className: 'hammer_input_logic_node_val_type', placeholder: '数据类型'});

    self.inputs = new Emt('div').setPros({className: 'hammer_input_logic_node_inputs'});
    self.btns = new Emt('div').setPros({className: 'hammer_input_logic_node_btns'});
    var div_title = new Emt('div').setPros({className: 'hammer_logic_title'}).addNodes([
        self.inputs.addNodes([
            self.input_name,
            self.input_desc,
        ]),
        self.btns.addNodes([
            new Emt('label').setPros({title: 'debug是否生效，数据会保留'}).addNodes([
                self.btn_debug, new Emt('span').setPros({textContent: 'debug'})
            ]),
            new Emt('label').addNodes([self.btn_add_val]),
            new Emt('label').addNodes([self.btn_add]),
            new Emt('label').setPros({title: '勾选上等同删除，和debug不一样，数据不会保留'}).addNodes([
                self.btn_remove
            ]),
        ]),
    ]);
    self.div_nodes = new Emt('div').setPros({className: 'hammer_logic_nodes'});
    self = self.addNodes([
        div_title,
        self.div_nodes
    ]);
    self.loadData = function (logicDataNode) {
        self.input_name.value = logicDataNode.name;
        self.btn_debug.checked = logicDataNode.debug;
        self.input_desc.value = logicDataNode.desc;

        self.type = logicDataNode.type;
        self.input_val.value = logicDataNode.val_type === 'obj' ? JSON.stringify(JSON.parse(logicDataNode.val), null, 4) : logicDataNode.val;
        self.input_val_type.value = logicDataNode.val_type;
        if (logicDataNode.name === 'mock') {
            console.log(self.input_val.value, logicDataNode);
        }


        logicDataNode.nodes.forEach(function (logicDataNode2) {
            var subNode = (new HammerLogicDomNode());
            if (logicDataNode2.type === 'node') {
                subNode = subNode.asNodeDom();
            } else {
                subNode = subNode.asValDom();
            }
            self.div_nodes.addNodes([
                subNode.loadData(logicDataNode2)
            ]);
        });
        return self;
    };
    self.asNodeDom = function () {
        self.btn_debug.checked = true;
        return self;
    };
    self.asValDom = function () {
        self.type = 'val';

        self.btn_add_val.parentNode.remove();
        self.btn_add.parentNode.remove();
        self.btn_debug.checked = true;

        self.input_name.placeholder = '假数据key';
        self.div_nodes.addNodes([self.input_val]);
        self.inputs.addNodes([self.input_val_type]);
        return self;
    };
    self.btn_add.addEventListener('click', function () {
        self.div_nodes.addNodes([
            (new HammerLogicDomNode()).asNodeDom()
        ]);
    });
    self.btn_add_val.addEventListener('click', function () {
        self.div_nodes.addNodes([
            (new HammerLogicDomNode()).asValDom()
        ]);
    });

    self.btn_remove.addEventListener('click', function () {
        self.remove();
    });

    self.getTree = function () {
        var obj = {
            name: self.input_name.value,
            desc: self.input_desc.value,
            debug: self.btn_debug.checked,
            type: self.type,
            val: self.input_val.value,
            val_type: self.input_val_type.value,
            nodes: []
        };
        if (obj.type === 'node') {
            self.div_nodes.childNodes.forEach(function (subNode) {
                if (typeof subNode.getTree === 'function') {
                    var obj2 = subNode.getTree();
                    obj.nodes.push(obj2);
                }
            });
        }

        return obj;
    };

    return self;
};