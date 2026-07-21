/**
 * Created by markedboat on 2019/12/19.
 */
"use strict";
/*jshint esversion: 6 */
/*globals document,cli,window,XPathResult,XMLHttpRequest,FormData,HTMLElement,Option,URLSearchParams */
/* exported Emt,domLoaded */


// Object.prototype.isStdArray = function () {
//     return typeof this.forEach === 'function';
// };
if (window.hasKl === undefined) {
    window.hasKl = true;

    let hasKl = false;
    try {
        let kl = kl || undefined;
        hasKl = true;
    } catch (e) {
        let kl = undefined;

    }
    if (hasKl === false) {
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
             * json 瑙ｇ爜
             * <br>!!!鍙鍘熷弬鏁版槸 object 锛屼笉浼氭鏌ユ槸涓嶆槸鏁扮粍
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
             * 灏嗗缁� object 杞寲鎴� from鐨刱ey=>name
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
             * 灏嗗缁� object 杞寲鎴� from鐨刱ey=>name
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
                //opts.xhrSetting.withCredentials 璁剧疆杩愯璺ㄥ煙鎿嶄綔
                if (typeof opts.xhrSetting === 'object') {
                    for (let key in opts.xhrSetting) {
                        request[key] = opts.xhrSetting[key]; // 璁剧疆杩愯璺ㄥ煙鎿嶄綔
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
                                            opts.error('璇锋眰缁撴灉涓嶈兘淇濆瓨涓� json');
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
                    kl.log('鍑洪敊浜�');
                    if (opts.error) opts.error(request.statusText, 'timeout');
                }, false);

                request.addEventListener("error", function () {
                    kl.log('鍑洪敊浜�');
                    if (opts.error) opts.error(request.statusText, 'error');
                }, false);

                request.addEventListener("abort", function () {
                    kl.log('涓柇浜�');
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
                opts.form = opts.form || new FormData();
                if (opts.data) {
                    kl.data2form(opts.form, opts.data, 0, '');
                }

                if (opts.method === 'GET') {
                    let str = (new URLSearchParams(opts.form).toString());
                    opts.url = opts.url.indexOf('?') === -1 ? `${opts.url}?${str}` : `${opts.url}&${str}`;
                }

                request.open((opts.method || "POST"), opts.url, true);
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
                                        return resolve({isOk: false, msg: 'json缁撴瀯寮傚父', request: {status: request.status, statusText: request.statusText, responseText: request.responseText}});
                                    }
                                }
                                //return resolve({isOk: true, result: result});
                                return resolve({isOk: true, result: result, request: {status: request.status, statusText: request.statusText, responseText: request.responseText}});
                            } else {
                                return resolve({isOk: false, msg: '璇锋眰寮傚父', request: {status: request.status, statusText: request.statusText, responseText: request.responseText}});
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
                                kl.error('hooks.flag閲嶅', flag);
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
                                //   e.preventDefault(); // 蹇呴』闃绘榛樿琛屼负
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
                         *  娣诲姞涓€涓嫋鎷介挬瀛�
                         *  @param {string} flag 閽╁瓙鏍囪瘑
                         *  @param {object} opt 閽╁瓙閰嶇疆
                         *  @param {function} opt.call drop浜嬩欢鐨勫洖璋�
                         *  @param {string} opt.srcClassname 鍙互鎷栨嫿鐨刢lassname,鐢ㄤ簬甯呴€�
                         *  @param {string} opt.dstClassname 鍙互鏀剧疆鐨� classname锛岀敤浜庣瓫閫�
                         *  @param {string} opt.draggingClassname 鎷栨嫿涓殑鏍峰紡 classname
                         *  @param {string} opt.hoverClassname 鍙互闃叉鐨勬牱瀛� classname
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
                            // 鍏ㄥ眬鐩戝惉鎷栨嫿浜嬩欢
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
                                    //cli.log('dragover:', e.target, e);
                                    e.preventDefault(); // 蹇呴』鏄�  dragover 鍜�   preventDefault,鎵嶈兘  闃绘榛樿琛屼负浠ュ厑璁告斁缃� 锛屼笉鐒朵笉鑳芥斁缃�
                                    kl.HTML.lib.drag.currHook.drag.hoverIn.call(e, 'dragover');
                                }
                            });

                            document.addEventListener('dragleave', (e) => {
                                //  cli.log('dragleave:', e.target, e);

                                if (kl.HTML.lib.drag.currHook.state === true && kl.HTML.lib.drag.currHook.drag.hoverOut.filter(e)) {
                                    kl.HTML.lib.drag.currHook.drag.hoverOut.call(e);
                                }
                            });

                            document.addEventListener('drop', (e) => {
                                //  cli.log('drop', e);
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


    if (window.kl === undefined) {
        window.kl = kl;
    }


    kl.log('loaded app.js');
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
        nodes = arguments;
    }
    nodes.forEach((node) => {
        if (typeof node === 'string') {
            self.innerHTML += node;
        } else if (node === false) {
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
 * 璁剧疆鍙ユ焺鍙婄储寮�
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
//蹇呴』鏄弻寮曞彿鐨�
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
 * 鑾峰彇鐖跺厓绱�
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
/** 鑾峰彇涓€涓埗鍏冪礌
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
        kl.log('璋冪敤閿欒锛岄潪select 涓嶈兘浣跨敤 addSelectItem 鏂规硶');
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
            kl.log('淇濈暀', i, index0, self.select_item_eles[index0]);
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
 kl.log('鏅€氭敾鍑�');
 }
 }

 var fit2 = function () {
 kl.log('fit2')
 }
 var hero1 = Object.create(hero);

 hero1.fit = hero1.fit.appendAfterFunction(fit2)
 hero1.fit()

 缁撴灉:
 init.js:1 鏅€氭敾鍑�
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