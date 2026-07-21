let view_json = function (input_data) {
    let json_box = new Emt('div').setPros({className: 'json_box'});
    console.log(input_data);
    var eachEle = function (data, key) {

        var is_int_key = typeof key === 'number';
        var str_key = is_int_key ? (key.toString() + ':') : '"' + key + '":';
        if (data !== null && typeof data === 'object') {
            var map_div = new Emt('div').setPros({className: 'json_map', eleType: 'map'});
            //var span = new Emt('span').setPros({textContent: str_key});
            var eles_fix1 = new Emt('div').setPros({className: 'eles_fix'});
            var eles_fix2 = new Emt('div').setPros({className: 'eles_fix'});
            var checkbox = new Emt('input').setPros({type: 'checkbox', className: 'json_key_checkbox'});

            var json_eles_div = new Emt('div').setPros({className: 'json_eles', jsonNodes: []});

            var json_val_div = new Emt('div').setPros({className: 'json_val_div', elesDiv: json_eles_div}).addNodes([
                //new Emt('p').setPros({textContent: ' '}),
                eles_fix1,
                json_eles_div,
                eles_fix2
            ]);
            map_div.addNodes([
                new Emt('div').setPros({className: 'json_key_div'}).addNodes([
                    new Emt('label').setPros({className: 'json_key'}).addNodes([
                        checkbox,
                        new Emt('span').setPros({textContent: str_key})
                    ])
                ]),
                json_val_div
            ]);
            map_div.toggleBtn = checkbox;
            map_div.valDiv = json_val_div;

            if (isVarArray(data)) {
                eles_fix1.textContent = '[';
                eles_fix2.textContent = ']';
                data.forEach(function (data2, index) {
                    var ele = eachEle(data2, index);
                    json_eles_div.jsonNodes.push(ele);
                    json_eles_div.addNodes([
                        ele
                    ]);
                });

            } else {
                eles_fix1.textContent = '{';
                eles_fix2.textContent = '}';
                for (var key2 in data) {
                    var ele = eachEle(data[key2], key2);
                    json_eles_div.jsonNodes.push(ele);
                    json_eles_div.addNodes([
                        ele
                    ]);
                }
            }
            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    json_val_div.classList.add('eles_close');
                    console.log(window.event, window.event.ctrlKey);
                    if (window.event.ctrlKey) {
                        console.log(json_val_div, json_eles_div.jsonNodes);
                        json_val_div.elesDiv.jsonNodes.forEach(function (ele) {
                            if (ele.eleType === 'map') {
                                if (ele.toggleBtn.checked === false) {
                                    ele.toggleBtn.checked = true;
                                    ele.valDiv.classList.add('eles_close');
                                }
                            }
                        });
                    }

                } else {
                    json_val_div.classList.remove('eles_close');
                }
            });

            var toggleAndCloseSubNodes = function () {
                console.log(json_val_div, json_eles_div.jsonNodes);
                if (checkbox.checked === false) {
                    checkbox.checked = true;
                    json_val_div.classList.add('eles_close');
                    //cli.log(window.event, window.event.ctrlKey);
                    console.log(json_val_div, json_eles_div.jsonNodes);
                    json_val_div.elesDiv.jsonNodes.forEach(function (ele) {
                        if (ele.eleType === 'map') {
                            if (ele.toggleBtn.checked === false) {
                                ele.toggleBtn.checked = true;
                                ele.valDiv.classList.add('eles_close');
                            }
                        }
                    });
                } else {
                    checkbox.checked = false;
                    json_val_div.classList.remove('eles_close');
                }
            };
            [eles_fix1, eles_fix2].forEach(function (btn) {
                btn.addEventListener('click', function () {
                    toggleAndCloseSubNodes();
                })
            });
            return map_div;
        } else {
            var str_data = (data === null) ? 'null' : (typeof data === 'string' ? '"' + data + '"' : data);
            //var str_key = typeof key === 'string' ? '"' + key + '"' : key;
            return new Emt('div').setPros({className: 'json_ele', eleType: 'ele'}).addNodes([
                new Emt('div').setPros({textContent: str_key, is_int_key: is_int_key, className: 'ele_key'}),
                new Emt('div').setPros({textContent: str_data, className: 'ele_val'})
            ]);
        }

    };
    var isVarArray = function (data) {
        return typeof data.forEach === 'function';
    };
    json_box.addNodes([eachEle(input_data, '__')]);
    return json_box;
};