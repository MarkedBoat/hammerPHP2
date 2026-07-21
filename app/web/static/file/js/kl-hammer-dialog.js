var HammerDialog = function () {
    let self = this;
    self.vals = [];
    self.dom = new Emt('div').setPros({className: 'hammer_dialog_bg_div'}).setStyle({
        //width: document.body.scrollWidth + 'px',
        //height: document.body.scrollHeight + 'px',
        width: window.outerWidth + 'px',
        height: window.outerWidth + 'px',
    });
    self.dom.classList.add('hide');
    self.inner_div = new Emt('div').setPros({className: 'hammer_dialog_inner_div'});
    self.outter_div = new Emt('div').setPros({className: 'hammer_dialog_outter_div'});
    self.title_div = new Emt('div').setPros({className: 'hammer_dialog_title_div', textContent: '默认标题'});
    self.content_div = new Emt('div').setPros({className: 'hammer_dialog_content_div'});
    self.content_tmp_div = new Emt('div').setPros({className: 'hammer_dialog_content_tmp_div'});
    self.cls_btn = new Emt('button').setPros({textContent: '关闭', className: 'hammer_dialog_close_btn'});
    document.body.appendChild(self.dom);
    self.dom.addNodes([
        self.outter_div.addNodes([
            self.inner_div.addNodes([
                self.title_div,
                self.content_div.addNodes([
                    self.content_tmp_div
                ]),
                self.cls_btn
            ])
        ])
    ]);

    self.showDialog = function () {
        self.dom.classList.remove('hide');
        //self.dom.classList.remove('hammer_hide_dailog');
        //self.dom.classList.add('hammer_show_dailog');

    };
    self.hideDialog = function () {
        self.dom.classList.add('hide');
        //self.dom.classList.remove('hammer_show_dailog');
        //self.dom.classList.add('hammer_hide_dailog');
    };
    self.addContentDom = function (dom) {
        self.content_tmp_div.addNodes([dom]);
    };
    self.clearContentDom = function () {
        self.content_tmp_div.remove();
        delete self.content_tmp_div;
        self.content_tmp_div = new Emt('div').setPros({className: 'hammer_dialog_content_tmp_div'});
        self.content_div.addNodes([
            self.content_tmp_div
        ]);
    };
    self.setTitleText = function (str) {
        self.title_div.textContent = str;
    };
    self.cls_btn.addEventListener('click', function () {
        self.hideDialog();
    });
    /**
     * 被覆盖得方法，等被关闭前调用
     */
    self.beforeClose = function () {

    };

    self.setAsMsg = function () {
        self.title_div.textContent = '提示信息';
        self.cls_btn.remove();
        return self;
    };
    self.showMsg = function (text, ttl) {
        self.content_tmp_div.textContent = text;
        self.showDialog();
        setTimeout(function () {
            self.hideDialog();
        }, ttl);
    };
    return self;
};
