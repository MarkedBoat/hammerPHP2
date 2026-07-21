let rows = temp1.rows;
let currentIndex = 5;

window.tmp_i = 1;

// 设置定时器每隔15秒点击下一个
const intervalId = setInterval(() => {
    window.tmp_i += 1;
    if (window.tmp_i % 5 !== 0) {
        return false;
    }
    currentIndex++;
    console.log(currentIndex);

    let sn = rows[currentIndex].cells[0].textContent;
    let title = rows[currentIndex].cells[1].textContent;
    if (rows[currentIndex].innerHTML.indexOf('color: rgb(174, 174, 174);') > -1) {
        console.error(`${sn} ${title} 不能下载`);
        return false;
    }

    let a = null;
    if (rows[currentIndex].cells[2].getElementsByClassName('icn-dl').length > 0) {
        a = rows[currentIndex].cells[2].getElementsByClassName('icn-dl')[0];
    } else {
        console.error(`${sn} ${title} 没下载按钮`);
        return false;
    }

    if (currentIndex < rows.length) {
        console.log(`${sn} ${title} `);

        a.click();

    } else {
        // 所有元素都已点击，清除定时器
        clearInterval(intervalId);
        console.log('所有元素已点击完毕');
    }
}, 1000);