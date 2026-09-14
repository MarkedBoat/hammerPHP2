<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="../file/js/kl-hammer.20240811.js" charset="UTF-8"></script>

    <title>ffmpeg转化后的</title>
    <style>
        #div_projects {
            width: 100%;
            float: left;
        }

        .item_list {
            width: 100%;
            float: left;
        }

        .item_list > button {
            display: block;
            width: 90%;
            float: left;
            margin-top: 0.5em;
            text-align: left;
        }

        .hidden {
            display: none !important;
        }

        .video_player_div {
            position: fixed;
            top: 0;
            left: 0;
            width: 90%;
            height: 90%;
            z-index: 999;
        }

        .video_player_div > video {
            max-width: 100%;
            max-height: 100%;

        }

        .item_on_fcous {
            border: 0.3em solid #666;
        }

        .conv-task {
            width: 100%;
            float: left;
            border: 1px solid #F00;
            padding: 1em;

            .src {
                width: 45%;
                float: left;

            }

            .res {
                width: 45%;
                float: left;
            }

            .op {

            }
        }

        .volume-input {
            width: 100%;
        }

    </style>
</head>
<body>
<div>
    <div>

    </div>
    <div id="root_div">

    </div>

</div>
<script></script>
<script>


    domLoaded(function () {
        let rootDiv = kl.id('root_div');
        let itemListDiv = new Emt('div', 'class="item_list"');
        let videoPlayerDiv = new Emt('div', 'class="video_player_div hidden"');
        let playerControllerDiv = new Emt('div', 'class="video_player_ctrl_div "');
        let videoPlayer = new Emt('video', 'controls="controls"', '', {volume: 0.1});
        let volumeInput = new Emt('input', 'type="range" class="volume-input"', '', {max: 100, value: 5});
        rootDiv.addNodes([
            volumeInput,
            videoPlayerDiv.addNodes([
                playerControllerDiv,
                videoPlayer,
            ]),
            itemListDiv,
        ]);

        let functionPlay = (item) => {
            videoPlayerDiv.classList.remove('hidden');
            videoPlayer.volume = parseInt(volumeInput.value) / 100;
            videoPlayer.src = item.src;
            videoPlayer.play();
            console.log(videoPlayer);
        }
        let functionPause = () => {
            videoPlayerDiv.classList.add('hidden');
            videoPlayer.pause();
        }

        document.addEventListener('keydown', function (e) {
            console.log('keydown', e.code);
            // 37 l  38 top  39 right  40 down
            switch (e.code) {
                case'Enter':
                    console.log('player div ok');

                    if (videoPlayer.paused) {
                        videoPlayer.play();
                    } else {
                        videoPlayer.paused();
                    }
                    break;
                case 'Escape':
                case 'Backquote':
                    console.log('player div exit');
                    videoPlayerDiv.classList.add('hidden');
                    videoPlayer.pause();
                    break;
                case 'ArrowLeft':
                    videoPlayer.currentTime += 15;
                    break;
                case 'ArrowUp':
                    videoPlayer.volume += 0.02;
                    break;
                case 'ArrowRight':
                    videoPlayer.currentTime -= 15;
                    break;
                case 'ArrowDown':
                    videoPlayer.volume -= 0.02;
                    break;
            }
            // e.preventDefault();
            // return false;


        });


        kl.ajax({
            url: '/dev/v1/KL_PC/getFfmpeged',
            data: {file: '/data/sysadv.debug'},
            success: function (file_result) {
                console.log(file_result);
                let list = kl.getValByPath(file_result, 'data.list');
                if (list && Array.isArray(list)) {
                    console.log(file_result);
                    list.forEach((pairInfo, i) => {
                        let infospan = new Emt('span');
                        let opBtn = new Emt('button', 'type="button" class="hidden"',);

                        let srcDiv = new Emt('div', 'class="src"');
                        let resDiv = new Emt('div', 'class="res"');
                        let opDiv = new Emt('div', 'class="op"').addNodes([
                            infospan, opBtn
                        ]);

                        let div = new Emt('div', 'class="conv-task"').addNodes([
                            srcDiv, resDiv, opDiv
                        ]);
                        let dirname = false;
                        if (pairInfo.dirMd5 === false) {
                            itemListDiv.addNode(div);
                        } else {
                            dirname = pairInfo.dir;
                            if (itemListDiv[pairInfo.dirMd5] === undefined) {

                                itemListDiv[pairInfo.dirMd5] = new Emt('div', 'class="dir-conv-task"');
                                itemListDiv[pairInfo.dirMd5].pairInfos = [];
                                let dirOpBtn = new Emt('button', 'type="button" class=""', '清理目录');


                                itemListDiv.addNode(itemListDiv[pairInfo.dirMd5].addNodes([
                                    new Emt('p').addNodes([
                                        new Emt('strong', '', pairInfo.dir), dirOpBtn,
                                    ])
                                ]));
                                dirOpBtn.addEventListener('click', async () => {
                                    if (window.confirm('确定清理目录?') === false) {
                                        return false;
                                    }
                                    let pairInfos = itemListDiv[pairInfo.dirMd5].pairInfos;
                                    for (const [tmp_j, tmpPairInfo] of pairInfos.entries()) {
                                        await kl.ajax({
                                            url: '/dev/v1/KL_PC/convOK',
                                            data: {pairInfo: tmpPairInfo.pairInfo},
                                            type: 'json',
                                            async: true,
                                        }).then(aysncRes => {
                                            console.log(tmp_j, aysncRes.result);
                                            let op_res = aysncRes.result;
                                            if (op_res && op_res.data && op_res.data.sta) {
                                                tmpPairInfo.pairDiv.remove();
                                                functionPause();
                                            } else {
                                                alert('报错了');
                                            }
                                        });
                                    }
                                });
                            }
                            itemListDiv[pairInfo.dirMd5].addNode(div);
                            itemListDiv[pairInfo.dirMd5].pairInfos.push({pairInfo: pairInfo, srcDiv: srcDiv, opBtn: opBtn, pairDiv: div});
                        }


                        let items = [];
                        let srcSize = 0;
                        let resSize = 0;
                        if (pairInfo.src) {
                            items.push(pairInfo.src);
                            let btn = new Emt('button', 'type="button"', 'SRC:' + pairInfo.src.title.replace(dirname, ''));
                            srcDiv.addNode(btn);
                            srcSize = pairInfo.src.filesize;

                            btn.addEventListener('click', () => {
                                event.preventDefault(); //阻止冒泡
                                functionPlay(pairInfo.src);
                            });
                        }
                        if (pairInfo.res) {
                            items.push(pairInfo.res);
                            if (pairInfo.cover === undefined && pairInfo.tmp === undefined) {
                                let btn = new Emt('button', 'type="button"', 'RES:' + pairInfo.res.title.replace(dirname, ''));
                                resDiv.addNode(btn);
                                resSize = pairInfo.res.filesize;

                                btn.addEventListener('click', () => {
                                    event.preventDefault(); //阻止冒泡
                                    functionPlay(pairInfo.res);
                                    opBtn.classList.remove('hidden');

                                });

                                let rate = (resSize / srcSize * 100).toFixed(2).toString();

                                let srcM = parseInt(srcSize / 1024 / 1024);
                                let resM = parseInt(resSize / 1024 / 1024);

                                infospan.textContent = `${rate}% (${srcM}M->${resM}M)`;
                                opBtn.textContent = `清理`;
                                opBtn.addEventListener('click', () => {
                                    kl.ajax({
                                        url: '/dev/v1/KL_PC/convOK',
                                        data: {pairInfo: pairInfo},
                                        type: 'json',
                                        success: function (op_res) {
                                            if (op_res && op_res.data && op_res.data.sta) {
                                                alert('成功');
                                                div.remove();
                                                functionPause();
                                            } else {
                                                alert('报错了');
                                            }
                                        },
                                        error: (msg) => {
                                            alert(msg);
                                        },
                                    });
                                });
                            } else {
                                if (pairInfo.cover !== undefined) {
                                    resDiv.addNode(kl.HTML.SPAN('', 'append_cover_ing'));
                                } else {
                                    resDiv.addNode(kl.HTML.SPAN('', 'conv tmp'));
                                }

                            }
                        } else {
                            resDiv.addNode(kl.HTML.SPAN('', 'no res'));
                        }


                    });
                } else {
                    alert(file_result.msg || '未知');
                }
            },
            type: 'json'
        });


    })

</script>
</body>
</html>