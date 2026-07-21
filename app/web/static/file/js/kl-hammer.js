/**
 * Created by markedboat on 2019/12/19.
 */
"use strict";
/*jshint esversion: 6 */
/*globals document,console,window,XPathResult,XMLHttpRequest,FormData,HTMLElement,Option,URLSearchParams */
/* exported Emt,domLoaded */


// Object.prototype.isStdArray = function () {
//     return typeof this.forEach === 'function';
// };


if (typeof window.hasKl === 'undefined') {
    window.hasKl = true;
    var kl = {
        opt: {log: true},
        log: console.log,
        warrning: console.warn,
        error: console.error,

        __log: console.log,
        __warn: console.warn,
        __error: console.error,


        isset: function (arg) {
            return typeof arg !== 'undefined';
        },
        id: function (id) {
            return document.getElementById(id);
        },

        getValByPath: function (object, keysPath, defaultVal) {
            if (object === undefined) {
                return defaultVal || undefined;
            }

            let keys = keysPath.replace(/\]\[/ig, '.').replace(/\]/ig, '').replace(/\[/ig, '.').split('.').filter(v => {
                return v.length > 0;
            });
            if (keys.length === 0) {
                return object;
            }
            let last = keys.splice(-1);
            let last_obj = [object].concat(keys).reduce(function (a, b) {
                return (a[b] === undefined || typeof a[b] !== 'object') ? {} : a[b];
            });
            if (defaultVal === undefined) {
                return ((last_obj[last] === null) ? undefined : last_obj[last]);
            } else {
                return ((last_obj[last] === null) ? undefined : last_obj[last]) || defaultVal;
            }

        },
        setValByPath: function (object, keysPath, value) {
            let keys = keysPath.split('.');
            let last = keys.splice(-1);
            [object].concat(keys).reduce(function (a, b) {
                if (a[b] === undefined) a[b] = {};
                return a[b];
            })[last] = value;
            return kl;
        },

        isUndefined: function (baseVar, attr_path) {
            let tmp_ar = attr_path.split('.');
            return tmp_ar.reduce(function (base_var, attr) {
                // kl.log(base_var, attr, base_var[attr], 'xxxx');
                return base_var === undefined || base_var === null || typeof base_var[attr] === 'undefined' ? undefined : base_var[attr];
            }, baseVar) === undefined;
        },
        xpathSearch: function (xpath, context) {
            let nodes = [];
            try {
                let doc = (context && context.ownerDocument) || window.document;
                let results = doc.evaluate(xpath, context || doc, null, XPathResult.ANY_TYPE, null);
                let node;
// while (node = results.iterateNext()) {
//                 nodes.push(node);
//             }
                while (true) {
                    node = results.iterateNext();
                    if (node) {
                        nodes.push(node);
                    } else {
                        break;
                    }
                }
            } catch (e) {
                throw e;
            }
            return nodes;
        },
        /**
         * json 解码
         * <br>!!!只要原参数是 object ，不会检查是不是数组
         * @param sourceData
         * @param defaultValue
         * @returns {{}|any}
         */
        jsonDecode: function (sourceData, defaultValue) {
            if (sourceData === null || sourceData === undefined) {
                return defaultValue;
            }
            let sourceDataType = typeof sourceData;
            let res;
            if (sourceDataType === 'string') {
                try {
                    res = JSON.parse(sourceData);
                    return res;
                } catch (e) {
                    return defaultValue;
                }
            } else {
                if (sourceDataType === 'object') {
                    return sourceData;
                }
                return defaultValue;
            }
        },


        getCookie: function (cookie_name) {
            let cks = document.cookie.split(';');
            for (let i = 0; i < cks.length; i++) {
                if (cks[i].search(cookie_name) !== -1) {
                    return decodeURIComponent(cks[i].replace(cookie_name + '=', ''));
                }
            }
        },

        setCookie: function (name, val, day, domain) {
            let date = new Date();
            date.setTime(date.getTime() + day * 24 * 3600 * 1000);
            let time_out = date.toGMTString();
            //kl.log(time_out, val);
            document.cookie = name + '=' + encodeURIComponent(val) + ';expires=' + time_out + ';path=/;domain=' + domain;
        },
        /**
         * 将多维 object 转化成 from的key=>name
         * @param fromData
         * @param input_data
         * @param level
         * @param name_root
         */
        data2form: function (fromData, input_data, level, name_root) {
            if (level === 0) {
                for (let k in input_data) {
                    if (typeof input_data[k] === 'object') {
                        kl.data2form(fromData, input_data[k], 1, k);
                    } else {
                        fromData.append(k, input_data[k]);
                    }
                }
            } else {
                for (let k in input_data) {
                    if (typeof input_data[k] === 'object') {
                        kl.data2form(fromData, input_data[k], level + 1, name_root + '[' + k + ']');
                    } else {
                        fromData.append(name_root + '[' + k + ']', input_data[k]);
                    }
                }
            }
        },


        /**
         * 将多维 object 转化成 from的key=>name
         * @param dstList
         * @param input_data
         * @param level
         * @param name_root
         */
        data2list: function (dstList, input_data, level, name_root) {
            if (level === 0) {
                for (let k in input_data) {
                    if (typeof input_data[k] === 'object') {
                        kl.data2list(dstList, input_data[k], 1, k);
                    } else {
                        dstList.push({key: k, val: input_data[k]});
                    }
                }
            } else {
                for (let k in input_data) {
                    if (typeof input_data[k] === 'object') {
                        kl.data2list(dstList, input_data[k], level + 1, name_root + '[' + k + ']');
                    } else {
                        dstList.push({key: name_root + '[' + k + ']', val: input_data[k]});
                    }
                }
            }
        },


        /**
         *
         * @param opts
         */
        ajax: function (opts) {
            let request = new XMLHttpRequest();
            opts.httpOkCodes = opts.httpOkCodes || [200];
            if (opts.httpOkCodes.indexOf(200) === -1) {
                opts.httpOkCodes.push(200);
            }
            request.timeout = (opts.timeout || 30) * 1000;
            request.responseType = opts.responseType || request.responseType;
            //opts.xhrSetting.withCredentials 设置运行跨域操作
            if (typeof opts.xhrSetting === 'object') {
                for (let key in opts.xhrSetting) {
                    request[key] = opts.xhrSetting[key]; // 设置运行跨域操作
                }
            }

            if (opts.async !== true) {
                request.addEventListener("load", function () {
                    if (typeof opts.onload === 'function') {
                        opts.onload(request);
                    } else {
                        if (opts.httpOkCodes.indexOf(request.status) !== -1) {
                            let result = request.responseText;
                            if (opts.type === 'json') {
                                try {
                                    result = JSON.parse(request.responseText);
                                } catch (e) {
                                    if (opts.error) {
                                        opts.error('请求结果不能保存为 json');
                                    }
                                }
                            }
                            opts.success(result);
                        } else {
                            if (opts.error) {
                                opts.error(request.status + ':' + request.statusText);
                            }
                        }
                    }

                }, false);
            }

            request.addEventListener("timeout", function () {
                kl.log('出错了');
                if (opts.error) opts.error(request.statusText, 'timeout');
            }, false);

            request.addEventListener("error", function () {
                kl.log('出错了');
                if (opts.error) opts.error(request.statusText, 'error');
            }, false);

            request.addEventListener("abort", function () {
                kl.log('中断了');
                if (opts.error) opts.error(request.statusText, 'abort');
            }, false);

            if (opts.progress) {
                request.upload.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        opts.progress(evt.loaded, evt.total);
                    }
                }, false);
            }


            //request.onreadystatechange = requestCallback;
            if (opts.postKV !== undefined) {
                opts.method = 'POST';
            }

            if (opts.method === 'GET') {
                let param = Object.assign(opts.getKV || {}, opts.data || {}, opts.form);
                let str = (new URLSearchParams(param).toString());
                opts.url = opts.url.indexOf('?') === -1 ? `${opts.url}?${str}` : `${opts.url}&${str}`;
            } else {
                opts.method = 'POST';
                if (opts.getKV !== undefined) {
                    let str = (new URLSearchParams(opts.getKV).toString());
                    opts.url = opts.url.indexOf('?') === -1 ? `${opts.url}?${str}` : `${opts.url}&${str}`;
                }
                if (typeof opts.postKV === 'string') {
                    opts.form = opts.postKV;
                } else if (typeof opts.data === 'string') {
                    opts.form = opts.data;
                } else {
                    opts.form = opts.form || new FormData();
                    if (opts.data) {
                        kl.data2form(opts.form, opts.data, 0, '');
                    }
                }

            }

            request.open((opts.method), opts.url, true);
            if (opts.isAjax !== false) {
                request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            }
            //request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            if (opts.headers && typeof opts.headers.forEach === 'function') {
                opts.headers.forEach((header_ar) => {
                    request.setRequestHeader(header_ar[0], header_ar[1]);
                });
            }


            if (opts.async === true) {
                return new Promise(function (resolve) {
                    request.send(opts.form);
                    request.onload = function () {
                        if (request.status === 200 || opts.httpOkCodes.indexOf(request.status) !== -1) {
                            let result = request.responseText;
                            if (opts.type === 'json') {
                                try {
                                    result = JSON.parse(request.responseText);
                                } catch (e) {
                                    return resolve({
                                        isOk: false,
                                        msg: 'json结构异常',
                                        request: {status: request.status, statusText: request.statusText, responseText: request.responseText}
                                    });
                                }
                            }
                            //return resolve({isOk: true, result: result});
                            return resolve({
                                isOk: true,
                                result: result,
                                request: {status: request.status, statusText: request.statusText, responseText: request.responseText}
                            });
                        } else {
                            return resolve({
                                isOk: false,
                                msg: '请求异常',
                                request: {status: request.status, statusText: request.statusText, responseText: request.responseText}
                            });
                            //return reject(request.status + ':' + request.statusText);
                        }
                    };
                });
            } else {
                request.send(opts.form);
            }
            return request;
        },
        getStack: function () {
            //    kl.log.apply(function(){},arguments)
            return new Error().stack.replace('Error', 'Stack');
        },

        HTML: {
            lib: {
                tableCellResize: {
                    hasAddEventListener: false,
                    isResizing: false,
                    currentResizer: false,
                    startX: 0,
                    startWidth: 0,
                    handleKlTableCellResizeMouseMove: function (e) {
                        // kl.log('mouse move',isResizing,currentResizer);
                        if (!kl.HTML.lib.tableCellResize.isResizing || !kl.HTML.lib.tableCellResize.currentResizer) {
                            return false;
                        }
                        const width = kl.HTML.lib.tableCellResize.startWidth + e.clientX - kl.HTML.lib.tableCellResize.startX;
                        kl.HTML.lib.tableCellResize.currentResizer.parentElement.style.width = width + 'px';
                    },

                    stopKlTableCellResizeResize: function () {
                        kl.HTML.lib.tableCellResize.isResizing = false;
                        kl.HTML.lib.tableCellResize.currentResizer = false;
                        //   document.removeEventListener('mousemove', handleKlTableCellResizeMouseMove);
                        //   document.removeEventListener('mouseup', stopKlTableCellResizeResize);
                    },
                    init: function () {
                        if (kl.HTML.lib.tableCellResize.hasAddEventListener === true) {
                            return false;
                        }
                        kl.HTML.lib.tableCellResize.hasAddEventListener = true;
                        document.addEventListener('mousemove', kl.HTML.lib.tableCellResize.handleKlTableCellResizeMouseMove);
                        document.addEventListener('mouseup', kl.HTML.lib.tableCellResize.stopKlTableCellResizeResize);

                        document.body.addNodes([
                            kl.HTML.STYLE().setPros({
                                innerHTML: `
        .kl-table {
            border-collapse: collapse;
            width: 100%;
        }

        .kl-table th, .kl-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            position: relative;
        }

        .kl-table .kl-cell-resizer {
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            cursor: col-resize;
        }
                        `
                            }),
                        ]);
                    }
                },
                drag: {
                    hasAddEventListener: false,
                    currDrag: false,
                    currHook: false,
                    hookKV: {
                        //flag:  {flag:falg,state: true},
                    },
                    common: {
                        addDraggingStyle: (e) => {
                            e.target.classList.add('kl-in-dragging');
                        },
                        removeDraggingStyle: (e) => {
                            e.target.classList.remove('kl-in-dragging');
                        },
                        addHoverStyle: (e) => {
                            e.target.classList.add('kl-drag-hover');
                        },
                        removeHoverStyle: (e) => {
                            e.target.classList.remove('kl-drag-hover');
                        },
                    },
                    addHook: function (flag, opt) {
                        console.log(flag, opt);
                        kl.HTML.lib.drag.init();
                        if (kl.HTML.lib.drag.hookKV[flag]) {
                            kl.error('hooks.flag重复', flag);
                            return false;
                        }
                        opt = opt || {};
                        let tmp = {flag: flag, state: true,};
                        tmp.drag = opt.drag || {};
                        tmp.drag.start = tmp.drag.start || {};
                        tmp.drag.end = tmp.drag.end || {};
                        tmp.drag.hoverIn = tmp.drag.hoverIn || {};
                        tmp.drag.hoverOut = tmp.drag.hoverOut || {};
                        tmp.drop = opt.drop || {};

                        tmp.drag.start.filter = tmp.drag.start.filter || function () {
                            return true;
                        };
                        tmp.drag.start.call = tmp.drag.start.call || kl.HTML.lib.drag.common.addDraggingStyle;

                        tmp.drag.end.filter = tmp.drag.end.filter || function () {
                            return true;
                        };
                        tmp.drag.end.call = tmp.drag.end.call || kl.HTML.lib.drag.common.removeDraggingStyle;


                        tmp.drag.hoverIn.filter = tmp.drag.hoverIn.filter || function (e) {
                            //   e.preventDefault(); // 必须阻止默认行为
                            return true;
                        };
                        tmp.drag.hoverIn.call = tmp.drag.hoverIn.call || kl.HTML.lib.drag.common.addHoverStyle;


                        tmp.drag.hoverOut.filter = tmp.drag.hoverOut.filter || function () {
                            return true;
                        };
                        tmp.drag.hoverOut.call = tmp.drag.hoverOut.call || kl.HTML.lib.drag.common.removeHoverStyle;


                        tmp.drop.filter = tmp.drop.filter || function () {
                            return true;
                        };
                        tmp.drop.call = tmp.drop.call || function (e) {
                            e.preventDefault();
                            kl.log(kl.HTML.lib.drag.currDrag);
                        };

                        kl.HTML.lib.drag.hookKV[flag] = tmp;
                        kl.log('addHook', tmp, kl.HTML.lib.drag.hookKV);
                    },
                    disableHook: function (flag) {
                        if (kl.HTML.lib.drag.hookKV[flag]) {
                            kl.HTML.lib.drag.hookKV[flag].state = false;
                        }

                    },
                    enableHook: function (flag) {
                        if (kl.HTML.lib.drag.hookKV[flag]) {
                            kl.HTML.lib.drag.hookKV[flag].state = true;
                        }
                    },
                    /**
                     *  添加一个拖拽钩子
                     *  @param {string} flag 钩子标识
                     *  @param {object} opt 钩子配置
                     *  @param {function} opt.call drop事件的回调
                     *  @param {string} opt.srcClassname 可以拖拽的classname,用于帅选
                     *  @param {string} opt.dstClassname 可以放置的 classname，用于筛选
                     *  @param {string} opt.draggingClassname 拖拽中的样式 classname
                     *  @param {string} opt.hoverClassname 可以防止的样子 classname
                     *
                     *  */
                    addSimpleHook: function (flag, opt) {
                        opt = opt || {};
                        let draggingElement;
                        let srcClassname = opt.srcClassname || 'drag-src-item';
                        let dstClassname = opt.dstClassname || 'drag-dst-item';
                        let draggingClassname = opt.draggingClassname || 'kl-in-dragging';
                        let hoverClassname = opt.hoverClassname || 'kl-drag-hover';
                        let call = opt.call || function (srcElement, dstElement) {
                            kl.log({srcElement: srcElement, dstElement: dstElement});
                        };

                        console.log(opt, srcClassname, opt.srcClassname, opt.srcClassname || 'drag-src-item');

                        let srcFilter = (e, eventType) => {
                            let res = e.target.classList.contains(srcClassname);
                            //   kl.log('srcFilter:', eventType, e.target, res,srcClassname);
                            return res;
                        };
                        let dstFilter = (e, eventType) => {
                            let res = e.target.classList.contains(dstClassname) && e.target !== draggingElement;
                            //  kl.log('dstFilter:', eventType, e.target, res,dstClassname);
                            return res;
                        };

                        kl.HTML.lib.drag.addHook(flag, {
                            drag: {
                                start: {
                                    filter: srcFilter,
                                    call: function (e) {

                                        draggingElement = e.target;
                                        draggingElement.classList.add(draggingClassname);
                                        //  kl.log('start:', e.target);
                                    },
                                },
                                end: {
                                    filter: srcFilter,
                                    call: function (e) {
                                        //  kl.log('end:', e.target);
                                        draggingElement.classList.remove(draggingClassname);
                                        draggingElement = false;
                                    },
                                },
                                hoverIn: {
                                    filter: dstFilter,
                                    call: function (e) {
                                        e.target.classList.add(hoverClassname);
                                    },
                                },
                                hoverOut: {
                                    filter: () => {
                                        return true;
                                    },
                                    call: function (e) {
                                        e.target.classList.remove(hoverClassname);
                                    },
                                },
                            },
                            drop: {
                                filter: dstFilter,
                                call: function (e) {
                                    let targetTagElement = e.target;
                                    e.target.classList.remove(hoverClassname);
                                    // kl.log('drag->dropOn', draggingElement, targetTagElement);
                                    call(draggingElement, targetTagElement);
                                },
                            },
                        });

                    },
                    init: function () {
                        if (kl.HTML.lib.drag.hasAddEventListener === true) {
                            kl.warn('drag.hasAddEventListener', true);
                            return false;
                        }
                        // 全局监听拖拽事件
                        document.addEventListener('dragstart', (e) => {
                            for (const [flag, hook] of Object.entries(kl.HTML.lib.drag.hookKV)) {
                                if (hook.state === true && hook.drag.start.filter(e, 'dragstart')) {
                                    kl.HTML.lib.drag.currHook = hook;
                                    hook.drag.start.call(e);
                                }
                            }
                        });

                        document.addEventListener('dragend', (e) => {
                            if (kl.HTML.lib.drag.currHook && kl.HTML.lib.drag.currHook.state === true && kl.HTML.lib.drag.currHook.drag.end.filter(e, 'dragend')) {
                                kl.HTML.lib.drag.currHook.drag.end.call(e);
                                kl.HTML.lib.drag.currHook = false;
                            }
                        });

                        document.addEventListener('dragover', (e) => {
                            if (kl.HTML.lib.drag.currHook.state === true && kl.HTML.lib.drag.currHook.drag.hoverIn.filter(e)) {
                                //console.log('dragover:', e.target, e);
                                e.preventDefault(); // 必须是  dragover 和   preventDefault,才能  阻止默认行为以允许放置 ，不然不能放置
                                kl.HTML.lib.drag.currHook.drag.hoverIn.call(e, 'dragover');
                            }
                        });

                        document.addEventListener('dragleave', (e) => {
                            //  console.log('dragleave:', e.target, e);

                            if (kl.HTML.lib.drag.currHook.state === true && kl.HTML.lib.drag.currHook.drag.hoverOut.filter(e)) {
                                kl.HTML.lib.drag.currHook.drag.hoverOut.call(e);
                            }
                        });

                        document.addEventListener('drop', (e) => {
                            //  console.log('drop', e);
                            if (kl.HTML.lib.drag.currHook.state === true && kl.HTML.lib.drag.currHook.drop.filter(e)) {
                                e.preventDefault();
                                kl.HTML.lib.drag.currHook.drop.call(e);
                            }
                        });

                        document.body.addNodes([
                            kl.HTML.STYLE().setPros({
                                innerHTML: `
        .kl-in-dragging {
            border:1px dashed #000 !important;
            box-sizing: border-box;
        } 

        

        .kl-drag-hover {
            border: 0.3em dashed #000 !important;
            box-sizing: border-box; 
        }
       
       
                        `
                            }),
                        ]);

                        kl.HTML.lib.drag.hasAddEventListener = true;
                    }
                },
            },
            /**
             @return {HTMLDivElement}
             */
            DIV: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('div', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLSelectElement}
             */
            SELECT: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('select', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLInputElement}
             */
            INPUT: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('input', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLInputElement}
             */
            INPUT_TEXT: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                attrsStr += ' type="text"';
                return new Emt('input', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLInputElement}
             */
            INPUT_NUMBER: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                attrsStr += ' type="number"';
                return new Emt('input', attrsStr, textContent, prototypeMap);
            },
            /**
             @return
             */
            Ymd: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                let span = new Emt('span', '', '', {});
                attrsStr += ' type="date"';
                let input = new Emt('input', attrsStr, '', {});
                let tmp_ymd_obj = {
                    getYmd: function () {
                        let res = parseInt(input.value.replace(/-/ig, ''));
                        if (typeof res === 'number' && !isNaN(res)) {
                            return res;
                        }
                        return 0;
                    },
                    setYmd: function (ymd) {
                        input.value = ymd.toString().replace(/^(\d{4})(\d{2})(\d{2})/ig, '$1-$2-$3');
                    },
                };

                span = Object.assign(span, tmp_ymd_obj);
                //let exp = /^\d+$/;
                Object.defineProperty(span, 'value', {
                    set: function (v) {
                        tmp_ymd_obj.setYmd(v);
                    },
                    get: function () {
                        return tmp_ymd_obj.getYmd();
                    },
                });
                input.setPros(prototypeMap);
                input.addEventListener('change', () => {
                    span.dispatchEvent(new Event('change'));
                });

                return span.addNodes([input]);

            },
            Ym: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                let span = new Emt('span', '', '', {});
                attrsStr += ' type="month"';
                let input = new Emt('input', attrsStr, '', {});


                let tmp_YM_obj = {
                    getYm: function () {
                        let res = parseInt(input.value.replace(/-/ig, ''));
                        if (typeof res === 'number' && !isNaN(res)) {
                            return res;
                        }
                        return 0;
                    },
                    setYm: function (ymd) {
                        input.value = ymd.toString().substring(0, 6).replace(/^(\d{4})(\d{2})/ig, '$1-$2');
                    },
                };

                span = Object.assign(span, tmp_YM_obj);
                //let exp = /^\d+$/;
                Object.defineProperty(span, 'value', {
                    set: function (v) {
                        tmp_YM_obj.setYmd(v);
                    },
                    get: function () {
                        return tmp_YM_obj.getYmd();
                    },
                });
                input.setPros(prototypeMap);
                input.addEventListener('change', () => {
                    span.dispatchEvent(new Event('change'));
                });
                return span.addNodes([input]);
            },
            /**
             *
             * @param attrsStr
             * @param textContent
             * @param prototypeMap  val:[trueVal,falseVal]
             * @return {HTMLInputElement}
             * @constructor
             */
            CHECKBOX: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                attrsStr += ' type="checkbox"';
                if (prototypeMap.vals && Array.isArray(prototypeMap.vals)) {
                    let vals = prototypeMap.vals;
                    let val = undefined;
                    if (prototypeMap.value !== undefined) {
                        val = prototypeMap.value;
                        delete prototypeMap.value;
                    }
                    /** @type {HTMLInputElement} */
                    let input = new Emt('input', attrsStr, textContent, prototypeMap);
                    Object.defineProperty(input, 'value', {
                        set: function (v) {
                            input.checked = v.toString() === vals[0].toString();
                        },
                        get: function () {
                            return input.checked === true ? vals[0] : vals[1];
                        },
                    });
                    if (val !== undefined) {
                        input.value = val;
                    }
                    return input;
                } else {


                    return new Emt('input', attrsStr, textContent, prototypeMap);
                }
            },
            /**
             @return {HTMLPreElement}
             */
            PRE: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('pre', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLLabelElement}
             */
            LABEL: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('label', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLSpanElement}
             */
            SPAN: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('span', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLEmbedElement}
             */
            EM: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('em', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLButtonElement}
             */
            BUTTON: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('button', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLButtonElement}
             */
            BUTTON_BUTTON: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                attrsStr += ' type="button"';
                return new Emt('button', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLButtonElement}
             */
            BUTTON_SUBMIT: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                attrsStr += ' type="submit"';
                return new Emt('button', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLTextAreaElement}
             */
            TEXTAREA: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('textarea', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLAnchorElement}
             */
            ANCHOR: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('a', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLUListElement}
             */
            ULIST: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('ulist', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLLIElement}
             */
            LI: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('li', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLHeadingElement}
             */
            HEADING: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('heading', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLImageElement}
             */
            IMAGE: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('image', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLFormElement}
             */
            FORM: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('form', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLObjectElement}
             */
            OBJECT: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('object', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLVideoElement}
             */
            VIDEO: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('video', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLCanvasElement}
             */
            CANVAS: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('canvas', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLAudioElement}
             */
            AUDIO: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('audio', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLBRElement}
             */
            BR: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('br', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLHRElement}
             */
            HR: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('hr', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLParagraphElement}
             */
            PARAGRAPH: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('p', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLMenuElement}
             */
            MENU: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('menu', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLHeadElement}
             */
            HEAD: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('head', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLBodyElement}
             */
            BODY: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('body', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLStyleElement}
             */
            STYLE: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('style', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLScriptElement}
             */
            SCRIPT: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('script', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLMetaElement}
             */
            META: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('meta', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLTitleElement}
             */
            TITLE: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('title', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLDataElement}
             */
            DATA: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('data', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLTemplateElement}
             */
            TEMPLATE: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('template', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLDataListElement}
             */
            DATALIST: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('datalist', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLMeterElement}
             */
            METER: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('meter', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLOutputElement}
             */
            OUTPUT: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('output', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLProgressElement}
             */
            PROGRESS: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('progress', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLDetailsElement}
             */
            DETAILS: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('details', attrsStr, textContent, prototypeMap);
            },
            /** @returns  {HTMLTableElement & {initResize:function():void }} */
            TABLE: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                let table = new Emt('table', attrsStr, textContent, prototypeMap);
                let _handle = {
                    initResize: function () {
                        kl.HTML.lib.tableCellResize.init();
                        table.classList.add('kl-table');
                        let ths = Object.values(kl.getValByPath(table, 'tHead.rows.0.cells', {}));
                        ths.forEach(th => {
                            if (th.resizer === undefined) {
                                th.resizer = kl.HTML.DIV('class="kl-cell-resizer"');
                                th.addNodes([th.resizer]);
                                th.resizer.addEventListener('mousedown', function (e) {
                                    //  kl.log('mouse mousedown');
                                    kl.HTML.lib.tableCellResize.isResizing = true;
                                    kl.HTML.lib.tableCellResize.currentResizer = e.target;
                                    kl.HTML.lib.tableCellResize.startX = e.clientX;
                                    kl.HTML.lib.tableCellResize.startWidth = parseInt(window.getComputedStyle(kl.HTML.lib.tableCellResize.currentResizer.parentElement).width, 10);
                                    e.preventDefault();
                                });
                            }
                        });
                    }
                };
                table = Object.assign(table, _handle);
                return table;
            },
            /**
             @return {HTMLTableSectionElement}
             */
            TABLESECTION: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('tablesection', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLTableRowElement}
             */
            TABLEROW: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('tablerow', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLTableCellElement}
             */
            TABLECELL: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('tablecell', attrsStr, textContent, prototypeMap);
            },
            /**
             @return {HTMLIFrameElement}
             */
            IFRAME: function (attrsStr = '', textContent = '', prototypeMap = {}) {
                return new Emt('iframe', attrsStr, textContent, prototypeMap);
            },
            destroyIframe: function (iframe) {
                if (!iframe) return;
                // 1. 清理内部状态
                iframe.onload = null;
                iframe.onerror = null;
                iframe.src = 'about:blank';
                try {
                    iframe.contentWindow.document.write('');
                    iframe.contentWindow.document.clear();
                } catch (e) {
                }
                // 2. 从DOM中移除
                if (iframe.parentNode) {
                    iframe.parentNode.removeChild(iframe);
                }
                // 3. 释放外部引用 (由调用方负责)
                // 若需在此完全清理，可传入数组等结构，或要求调用方传入引用变量
                iframe = null;
            },
            safeRemoveElement: function (element) {
                function safeRemove(node) {
                    // 无效节点或已无父节点则退出
                    if (!node || !node.parentNode) return;

                    // 仅对元素节点处理子元素递归（文本/注释节点无子元素）
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // 使用 children（仅元素子节点），避免处理文本节点等
                        // 注意：Element 的 children 永远存在（至少是空 HTMLCollection）
                        const children = node.children;
                        // 转为数组递归（确保 children 存在，但元素节点总是存在）
                        if (children) {
                            Array.from(children).forEach(child => safeRemove(child));
                        }

                        // 清理内联事件处理器（可选，减少内存占用）
                        const onevents = ['onclick', 'onload', 'onerror', 'onmessage', 'onresize', 'onscroll'];
                        for (const evt of onevents) {
                            if (evt in node) {
                                node[evt] = null;
                            }
                        }

                        // 如果你想删除自定义属性（谨慎！只删除你自己的挂载属性）
                        // 例如：只删除以 '_' 开头的属性
                        for (let key in node) {
                            if (node.hasOwnProperty(key) && key.startsWith('_')) {
                                try {
                                    delete node[key];
                                } catch (e) {
                                }
                            }
                        }
                    }

                    // 最后从 DOM 中移除节点（适用于任何节点类型）
                    node.remove();
                }

                safeRemove(element);
                return true;

            },
            copyOuterHtmlWithStyles: async function (element) {
                /**
                 * 复制带完整样式的 HTML 到剪贴板
                 * @param {HTMLElement} element 要复制的根元素
                 */
                async function copyWithStyles(element) {
                    // 1. 深克隆要复制的区域
                    const cloneContainer = element.cloneNode(true);

                    // 2. 递归处理所有元素节点
                    function inlineStyles(el, originalEl) {
                        if (!el.style) return;

                        // 获取计算样式（基于原始元素，因为克隆元素可能还未插入文档流）
                        const computed = window.getComputedStyle(originalEl);

                        // 将所有计算出的样式赋给克隆元素的内联 style
                        // 注意：computed 包含大量属性（约 300+），可选择性过滤
                        let styleText = '';
                        for (let i = 0; i < computed.length; i++) {
                            const prop = computed[i];
                            styleText += `${prop}: ${computed.getPropertyValue(prop)}; `;
                        }
                        el.style.cssText = styleText;

                        // 递归处理子元素
                        for (let i = 0; i < el.children.length; i++) {
                            if (el.children[i] && originalEl.children[i]) {
                                inlineStyles(el.children[i], originalEl.children[i]);
                            }
                        }
                    }

                    inlineStyles(cloneContainer, element);

                    // 3. 将带内联样式的克隆元素转为 HTML 字符串
                    return cloneContainer.outerHTML;
                    if (0) {
                        // 4. 写入剪贴板
                        try {
                            await navigator.clipboard.writeText(cloneContainer.outerHTML);
                            console.log('复制成功！');
                        } catch (err) {
                            console.error('复制失败：', err);
                        }
                    }
                }

                return await copyWithStyles(element);
            },

        },


    };

    Object.defineProperty(kl, '_log', {
        set: function (v) {
            let enable_fns = [];
            if (v === true) {
                enable_fns = ['log', 'warn', 'error'];
            } else if (typeof v === 'string') {
                enable_fns = v.split(',');
            } else if (Array.isArray(v)) {
                enable_fns = v;
            }
            ['log', 'warn', 'error'].forEach(function (fn_str) {
                if (enable_fns.indexOf(fn_str) >= 0) {
                    kl[fn_str] = kl['__' + fn_str];
                } else {
                    kl[fn_str] = function () {
                    };
                }
            });
            return kl;
        },

    });

}


/**
 *
 * @param tagName
 * @param attrsStr
 * @param textContent
 * @param prototypeMap
 * @returns {HTMLElement }
 * @constructor
 */
function Emt(tagName, attrsStr = '', textContent = '', prototypeMap = {}) {
    let ele = document.createElement(tagName);
    if (typeof attrsStr === 'string') {
        ele.setAttrsByStr(attrsStr, textContent || '');
    }
    if (typeof prototypeMap === 'object') {
        ele.setPros(prototypeMap);
    }
    return ele;
}


HTMLElement.prototype.addNode = function () {
    let self = this;
    for (let i = 0; i < arguments.length; i++) {
        if (typeof arguments[i] !== 'string') {
            this.appendChild(arguments[i]);
            arguments[i].boss = self;
            arguments[i].parent = self;
            if (typeof arguments[i + 1] === 'string') {
                if (arguments[i + 1]) self[arguments[i + 1]] = arguments[i];
            }
        }
    }
    return self;
};

HTMLElement.prototype.addNodes = function (nodes) {
    let self = this;
    if (!Array.isArray(nodes)) {
        nodes = [...arguments];
    }
    nodes.forEach((node) => {
        if (typeof node === 'string') {
            self.innerHTML += node;
        } else if (node === false) {
            //
        } else if (node === undefined) {
            //
        } else {
            node.boss = self;
            self.appendChild(node);
            if (node.eleName) {
                self[node.eleName] = node;
                if (node.eleParent) {
                    node.eleParent [node.eleName] = node;
                }
            }
        }
    });


    return self;
};

HTMLElement.prototype.setStyle = function (configs) {
    for (let attr in configs) {
        this.style[attr] = configs[attr];
    }
    return this;
};
HTMLElement.prototype.setPros = function (configs) {
    for (let attr in configs) {
        this[attr] = configs[attr];
    }
    return this;
};
/**
 * 设置句柄及索引
 * @param {Object} index_handler
 * @param index_name
 * @returns {HTMLElement}
 */
HTMLElement.prototype.setIndexHandler = function (index_handler, index_name) {
    index_handler[index_name] = this;
    this.indexHandler = index_handler;
    return this;
};
HTMLElement.prototype.setAttrs = function (configs, isAddPrototype = false) {
    for (let attr in configs) {
        this.setAttribute(attr, configs[attr]);
    }
    if (isAddPrototype) {
        for (let attr in configs) {
            this[attr] = configs[attr];
        }
    }
    return this;
};
//必须是双引号的
HTMLElement.prototype.setAttrsByStr = function (raw_attrs_str, textContent) {
    let tmp_ar = raw_attrs_str.replace(/=\s?"\s?/g, '=').replace(/"\s+/g, '" ').replace(/\s?:\s?/g, ':').split('" ');
    for (let ar_i = 0; ar_i < tmp_ar.length; ar_i++) {
        let tmp_str = tmp_ar[ar_i];
        let tmp_ar2 = tmp_str.split('=');
        if (tmp_ar2.length === 2) {
            this.setAttribute(tmp_ar2[0].replace(/\s/g, ''), tmp_ar2[1].replace(/(^\s)|(\s$)|"/g, ''));
        }
    }
    if (typeof textContent === 'string') {
        this.textContent = textContent;
    }
    return this;
};

HTMLElement.prototype.setEventListener = function (event, fn) {
    this.addEventListener(event, fn);
    return this;
};
HTMLElement.prototype.bindEvent = function (event, fn) {
    this.addEventListener(event, fn);
    return this;
};
/**
 * 获取父元素
 * @param deep
 * @return {HTMLElement[]|*[]}
 */
HTMLElement.prototype.getParentElements = function (deep) {
    deep = deep || 10;
    let parentELements = [];
    let cur = this;
    for (let i = 0; i < deep; i++) {
        if (cur.parentElement) {
            parentELements.push(cur.parentElement);
            cur = cur.parentElement;
        } else {
            break;
        }
    }
    return parentELements;
};
/** 获取一个父元素
 * return {HTMLElement|*|false}
 * */
HTMLElement.prototype.filterOneParentElement = function (filterFn, deep) {
    deep = deep || 10;
    let cur = this;
    let match = false;
    for (let i = 0; i < deep; i++) {
        if (cur.parentElement) {
            if (filterFn(cur) === true) {
                match = cur;
                break;
            }
            cur = cur.parentElement;
        } else {
            break;
        }
    }
    return match;
};

/**
 *
 * @param opts
 let opts = {
 path: 'premit.startTime',
 domData: domData
 }
 * @returns {HTMLElement}
 */
HTMLElement.prototype.bindData = function (opts) {
    opts.ele = this;
    opts.domData.bindData(opts);
    return this;
};


HTMLElement.prototype.toggleClassList = function (class_name, is_add) {
    if (typeof is_add === 'undefined') {
        this.classList.toggle(class_name);
    } else if (is_add) {
        this.classList.add(class_name);
    } else {
        this.classList.remove(class_name);
    }
    return this;
};

HTMLElement.prototype.select_item_vals = [];
HTMLElement.prototype.select_item_eles = [];

HTMLElement.prototype.addSelectItem = function (val, text, is_default) {
    let self = this;
    if (self.tagName === 'SELECT') {
        if (self.select_item_vals.indexOf(val) === -1) {
            self.select_item_vals.push(val);
            let opt = new Option(text, val);
            opt.is_default = is_default;
            opt.val = val;
            self.select_item_eles.push();
            self.add(opt);
            if (is_default) {
                self.value = val;
            }
        }
    } else {
        kl.log('调用错误，非select 不能使用 addSelectItem 方法');
    }
    return self;
};
/**
 *
 * @param list [ {val:xx,text:xx,is_default:true/false} ]
 * @returns {HTMLElement}
 */
HTMLElement.prototype.addSelectItemList = function (list) {
    let self = this;
    if (typeof list.forEach === 'function') {
        list.forEach(function (info) {
            self.addSelectItem(info.val || '', info.text || '', info.is_default || '');
        });
    }
    return this;
};
HTMLElement.prototype.clearSelectItems = function (keep_default) {
    let self = this;
    let index0 = 0;
    for (let i in self.select_item_eles) {
        if (keep_default === true && self.select_item_eles[index0].is_default === true) {
            kl.log('保留', i, index0, self.select_item_eles[index0]);
            index0 = index0 + 1;
        }
        self.select_item_eles[index0].remove();
    }
    if (self.select_item_eles.length > 0) {
        if (keep_default === true) {
            self.select_item_vals = [self.select_item_eles[0].val];
        } else {
            self.select_item_vals = [];
        }
    }

};

HTMLElement.prototype.removeAllChildNodes = function () {
    let es = Object.values(this.children);
    es.forEach((e) => {
        e.remove();
    });
};

HTMLSelectElement.prototype.setItems = function (items, selected_key) {
    let self = this;
    if (Array.isArray(items)) {
        items.forEach(function (item) {
            self.add(new Option(item.text, item.val));
        });
    }
    if (selected_key !== undefined) {
        self.value = selected_key;
    }
    return self;

};

Object.defineProperty(HTMLSelectElement.prototype, "setItems", {enumerable: false,});
/** *
 * @param items
 * @return {HTMLSelectElement}
 */
HTMLSelectElement.prototype.appendItems = function (items) {
    let self = this;
    if (Array.isArray(items)) {
        items.forEach(function (item) {
            self.add(new Option(item.text, item.val));
        });
    }
    return self;
};
Object.defineProperty(HTMLSelectElement.prototype, "appendItems", {enumerable: false,});

HTMLDivElement.prototype.initElementInterface = function () {
    let self = this;
    let htmlInterfaceMap = {
        value: {},
        text: {},
    };
    if (typeof self.value === "object") {
        for (const [k, inputElement] of Object.entries(self.value)) {
            Object.defineProperty(self, k, {
                set: function (v) {
                    inputElement.value = v;
                    htmlInterfaceMap.value[k] = inputElement;
                    return self;
                },
                get: function () {
                    return htmlInterfaceMap.value[k];
                },
            });
        }
    }
    if (typeof self.text === "object") {
        for (const [k, htmlElement] of Object.entries(self.text)) {
            Object.defineProperty(self, k, {
                set: function (v) {
                    htmlElement.textContent = v;
                    htmlInterfaceMap.text[k] = v;
                    return self;
                },
                get: function () {
                    return htmlInterfaceMap.text[k].toString();
                },
            });
        }
    }
};

/**

 var hero = {
 fit: function() {
 kl.log('普通攻击');
 }
 }

 var fit2 = function () {
 kl.log('fit2')
 }
 var hero1 = Object.create(hero);

 hero1.fit = hero1.fit.appendAfterFunction(fit2)
 hero1.fit()

 结果:
 init.js:1 普通攻击
 init.js:1 fit2


 * @param afterfn
 * @returns {function(): *}
 */
Function.prototype.appendAfterFunction = function (afterfn) {
    let _self = this;
    return function () {
        let ret = _self.apply(this, arguments);
        afterfn.apply(this, arguments);
        return ret;
    };
};


function domLoaded(fn) {
    document.addEventListener('DOMContentLoaded', function () {
        console.log('ready 1');
        fn();
    });
}

if (window.kl === undefined) {
    window.kl = kl;
}

window.isIframe = window !== top;
window.timeStr = (new Date()).toLocaleTimeString();
kl.log(`${window.timeStr}:loaded hammer.js,isIframe[${window.isIframe}]`, {
    href: document.location.href,
    title: document.title,
    document
});
