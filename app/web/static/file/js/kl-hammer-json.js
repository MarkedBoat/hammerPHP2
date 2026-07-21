let style_str='';
let view_json = function (data) {
    let json_box = new Emt('div');
    let line_root = new Emt('pre').setPros({className: 'json_lines noselect', deep: 0, jsonNodes: [], lineNodes: []});
    line_root.setAttrs({'unselectable':'on'});
    let json = JSON.stringify(data, null, 4);
    let strs = json.split("\n");
    let json_root = new Emt('pre').setPros({className: 'json_root', deep: 0});
    let deep_sets=[[],[]];
    json_box.addNodes([line_root, json_root]);

    //var curr_root = root;
    let curr_root = line_root;
    strs.forEach(function (str, index) {
        let btn = new Emt('label').setPros({className: 'json_extend_btn'});
        let line = new Emt('div').setPros({textContent: index + 1}).addNodes([btn]);
        line_root.addNodes([line]);
        let span = new Emt('div').setPros({textContent: str, clasName: 'level'});
        json_root.addNodes([span]);

        let str_ = str.trim().substr(-4);
        if (str_ === '{' || str_ === '": {' || str_ === '": [' || str_ === '[') {
            curr_root.jsonNodes.push(span);
            curr_root.lineNodes.push(line);
            if(typeof  deep_sets[curr_root.deep]==='undefined'){
                deep_sets.push([]);
            }
            deep_sets[curr_root.deep].push(line);
            line.setPros({
                deep: curr_root.deep + 1,
                parentRoot: curr_root,
                jsonNodes: [],
                lineNodes: []
            });
            curr_root = line;


            //cli.log('++', str, str_);
            line.className = ((str_ === '": {' ? 'object' : 'array') + ' deep' + line.deep.toString());

            line.node_action = function (type,is_ctrl) {
              //  cli.log(type);
                line.jsonNodes.forEach(function (node, index) {
                    if (typeof node.jsonNodes === 'object') {
                        if(!(type==='show' && is_ctrl))
                        node.node_action(type,is_ctrl);
                        //return false;
                    }
                    if (type === 'toggle') {
                        node.classList.toggle('hide');
                    } else if (type === 'hide') {
                        node.classList.add('hide');
                    } else if (type === 'show') {
                        node.classList.remove('hide');
                    } else {

                    }
                });

                line.lineNodes.forEach(function (node, index) {
                    if (typeof node.jsonNodes === 'object') {
                        if(!(type==='show' && is_ctrl))
                        node.node_action(type,is_ctrl);
                        //return false;
                    }
                    if (type === 'toggle') {
                        node.classList.toggle('hide');
                    } else if (type === 'hide') {
                        node.classList.add('hide');
                    } else if (type === 'show') {
                        node.classList.remove('hide');
                    } else {

                    }
                });
            };


            line.addNodes([function () {
                btn.addNodes([
                    new Emt('span').setPros({textContent: '+', className: 'json_expand_label'}),
                    new Emt('span').setPros({textContent: '-', className: 'json_collapse_label'})
                ]);
                line.btn_toggle=btn;
                btn.addEventListener('click', function () {
                    console.log(window.event.ctrlKey,line,line.deep,line.lineNodes,line.jsonNodes);
                    window.curr_line = line;
                    var action=btn.className.indexOf('json_extend_flag') === -1 ? 'hide' : 'show';
                    line.node_action(action,window.event.ctrlKey);
                    if(window.event.ctrlKey && action==='show' && line.lineNodes){
                        //line.node_action('hide',window.event.ctrlKey);
                        line.lineNodes.forEach(function(lower_line){
                           // lower_line.btn_toggle.click();
                        })
                    }
                   // cli.log(line);
                    btn.classList.toggle('json_extend_flag');
                    event.preventDefault(); //阻止冒泡
                }, false);
                return btn;
            }()]);


        } else if (str_ === '},' || str_ === '],' || str_ === '}' || str_ === ']') {
            //cli.log('++', str, str_);
            curr_root = curr_root.parentRoot;
            curr_root.jsonNodes.push(span);
            curr_root.lineNodes.push(line);
        } else {
            curr_root.jsonNodes.push(span);
            curr_root.lineNodes.push(line);
        }
    });
    return json_box;
};