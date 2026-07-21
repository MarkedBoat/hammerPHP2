// ==UserScript==
// @name         风行project UI美化
// @namespace    http://tampermonkey.net/
// @version      0.1
// @description  try to take over the world!
// @author       You
// @match        http://project.funshion.com/*
// @grant        none
//  ==/UserScript==

(function () {
    'use strict';
    var js = document.createElement('script');
    js.src = 'http://cdn-imfile1.fengmi.tv/inmi/js/hammer.js';
    document.body.appendChild(js);
    js.addEventListener('load', function () {
        document.body.appendChild(new Emt('style').setPros({
            id: 'id_kl_style',
            innerHTML: '' +
                'select,input[type="text"]{' +
                '-padding: 10px;\n' +
                'height: 35px;' +
                '}' +
                '.tabular label{' +
                'line-height: 2.5em;\n' +
                'font-size: 1.5em;\n' +
                '}' +
                'input[type="checkbox"] + label{' +
                'line-height: 2.5em;\n' +
                'font-size: 1.5em;\n' +
                '}' +
                'input[type="checkbox"]:checked + label{color:#FFF;background:#000;}' +
                '.hide{' +
                '    display: none;\n' +
                '}' +
                '#uploaded_jpg_view{' +
                '    position: fixed;\n' +
                '    top: 0;\n' +
                '    max-width: 100%;\n' +
                '    max-height: 100%;\n' +
                '    left: 0;\n' +
                '    height: auto;\n' +
                '    width: auto;\n' +
                '    background: #FFF;\n' +
                '    border: 5px solid #cef304;' +
                '}' +
                '#uploaded_jpgs{' +
                '    position: fixed;\n' +
                '    top: 0;\n' +
                '    left: 0;\n' +
                '    width: 420px;\n' +
                '    height: 400px;\n' +
                '    overflow-y: scroll;\n' +
                '    overflow-x: hidden;\n' +
                '    background: #FFF;\n' +
                '}' +
                '#uploaded_jpgs>div{' +
                '       display: block;' +
                '       float:left;' +
                '       width:400px;' +
                '       border:1px solid #000;' +
                '       padding:10px;' +
                '}' +
                '#uploaded_jpgs>div>img{' +
                '       display: block;' +
                '       float:left;' +
                '       width:400px;' +
                '       height:auto;' +
                '}' +
                ''
        }));
        console.log('ok2');
        //kl.id('issue_notes').parentElement.appendChild(new Emt('textarea').setPros({className:'wiki-edit',rows:10,cols:60,id:'paste_img'}))
        if (kl.id('issue_notes')) {
            kl.id('issue_notes').addEventListener('paste', function (event) {
                console.log('onpaste');
                var len = event.clipboardData.items.length;
                for (var i = 0; i < len; i++) {
                    console.log(event.clipboardData.items[i]);
                    if (event.clipboardData.items[i].type.match(/^image\//)) {
                        var fd = new FormData();
                        fd.append('file', event.clipboardData.items[i].getAsFile());
                        fd.append('filename', Math.random().toString().substr(2));
                        ajax({
                            url: 'http://content.fengmi.tv/upload.php',
                            form: fd,
                            isAjax: false,
                            success: function (data) {
                                console.log(data);
                                if (data.sta && data.sta === true) {
                                    console.log(data.url);
                                    kl.id('issue_notes').value += ("\n!" + data.url + "!");
                                }
                            },
                            error: function (error) {
                                console.log(error);
                            },
                            type: 'json'
                        });
                        //reader.readAsBinaryString(event.clipboardData.items[i].getAsFile());
                    }
                }
            });

            console.log('先选中，上传之后，按ctrl+e，可以选择附件中的图片');
            var uploaded_jpg_view = new Emt('img').setPros({id: 'uploaded_jpg_view', className: 'hide'});
            var div = new Emt('div').setPros({id: 'uploaded_jpgs', className: 'hide'});
            var book = new Emt('div');
            var btn_file = new Emt('input').setPros({type: 'file'});
            var input_file_line = new Emt('input').setPros({type: 'text'});
            var show_p = new Emt('p').setStyle({lineHeight: '1.8em'});
            var show_divs = document.getElementsByClassName('wiki');
            if (typeof show_divs[1] !== 'undefined') {
                show_divs[1].appendChild(show_p);
            }
            var strs = [];
            book.addNodes([btn_file, input_file_line]);
            document.body.appendChild(div);
            document.body.appendChild(uploaded_jpg_view);
            document.body.appendChild(book);
            uploaded_jpg_view.addEventListener('click', function () {
                this.classList.add('hide');
            });
            var tmp = document.getElementsByClassName('attachments');
            if (typeof tmp[0] !== 'undefined') {
                var as = tmp[0].getElementsByTagName('a');
                for (var i = 0; i < as.length; i++) {
                    if (as[i].href.indexOf('.jpg') !== -1) {
                        console.log(i, as[i]);
                        continue;
                    }
                    var img = new Emt('img').setPros({src: as[i].href});
                    div.addNodes([
                        new Emt('div').addNodes([
                            img,
                            new Emt('strong').setPros({textContent: as[i].textContent})
                        ])
                    ]);
                    img.clickFun = function (ctrlKey) {
                        console.log(ctrlKey, kl.id('issue_notes'));
                        if (ctrlKey) {
                            console.log('当前焦点', document.activeElement);
                            kl.id('issue_notes').value += ("\n!" + this.src + "!" + "\n####\n");
                            //document.activeElement.value += ("\n!" + this.src + "!" + "\n####\n");
                        } else {
                            uploaded_jpg_view.src = this.src;
                            uploaded_jpg_view.classList.remove('hide');
                        }
                    };
                }
            }

            var filereder = new FileReader();
            var page_num = 0;
            var file_block_size = 1024;
            input_file_line.addEventListener('change', function () {
                page_num = parseInt(this.value);
                console.log(page_num);
                getText(0);
            });
            document.onkeydown = function (e) {
                var keyCode = e.keyCode || e.which || e.charCode;
                var altKey = e.altKey;
                var ctrlKey = e.ctrlKey;
                //cli.log('键盘监听 按键',ctrlKey, keyCode);
                if (ctrlKey && keyCode == 69) {
                    div.classList.toggle('hide');
                    e.preventDefault();
                    return false;
                }

                if (ctrlKey && keyCode == 66) {
                    book.classList.toggle('hide');
                    e.preventDefault();
                    return false;
                }
                //←
                if (ctrlKey && keyCode == 37) {
                    getText(-1);
                    e.preventDefault();
                    return false;
                }
                //↑
                if (ctrlKey && keyCode == 38) {
                    console.log(input_file_line.value, strs[input_file_line.value]);
                    e.preventDefault();
                    return false;
                }
                //→
                if (ctrlKey && keyCode == 39) {
                    getText(1);
                    e.preventDefault();
                    return false;
                }
            };


            document.onclick = function (e) {
                var ctrlKey = e.ctrlKey;
                console.log('点击监听 按键', ctrlKey, typeof e.target.clickFun, typeof e.target.clickFun === 'function');
                if (typeof e.target.clickFun === 'function') {
                    e.target.clickFun(ctrlKey);
                    e.preventDefault();
                    return false;
                }
            };


            btn_file.addEventListener('change', function () {
                console.log(btn_file);
                var tmp = btn_file.files[0];
                console.log(tmp);
                //var fileType = tmp_img.type;
                // var fr = new FileReader();
                filereder.file = tmp;
                filereder.onload = function (evt) {
                    //cli.log(evt.target.file);
                    console.log(evt.target.result);
                    show_p.textContent = evt.target.result;
                    //    strs=evt.target.result.toString().split("\n");

                };
                input_file_line.value = localStorage.getItem('page_num');
                page_num = parseInt(input_file_line.value);

                //fr.readAsDataURL(tmp_img);
                // filereder.readAsText(filereder.file);

            });

            function getText(num) {
                page_num = page_num + num;
                input_file_line.value = page_num.toString();
                localStorage.setItem('page_num', page_num);
                var end = page_num * file_block_size;
                var file_start = (page_num - 1) * file_block_size - 6;
                if (end < 0) {
                    file_start = 0;
                    end = file_block_size;
                }
                filereder.readAsText(filereder.file.slice(file_start, end));
                console.log(file_start, end);
            }
        }
    });
})();
