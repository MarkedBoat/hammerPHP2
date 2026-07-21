var chars = {
    "a": {
        "width": 5,
        "lines": [
            "  _",
            " /_\\",
            "/   \\"
        ]
    },
    "b": {
        "width": 5,
        "lines": [
            "___",
            "|__)",
            "|__)"
        ]
    },
    "c": {
        "width": 5,
        "lines": [
            "____",
            "|",
            "|___"
        ]
    },
    "d": {
        "width": 5,
        "lines": [
            "___",
            "|  \\",
            "|__/"
        ]
    },
    "e": {
        "width": 5,
        "lines": [
            "____",
            "|___",
            "|___"
        ]
    },
    "f": {
        "width": 5,
        "lines": [
            "____",
            "|___",
            "|"
        ]
    },
    "g": {
        "width": 5,
        "lines": [
            "____",
            "| __",
            "|__]"
        ]
    },
    "h": {
        "width": 5,
        "lines": [
            "_  _",
            "|__|",
            "|  |"
        ]
    },
    "i": {
        "width": 3,
        "lines": [
            "_",
            "|",
            "|"
        ]
    },
    "j": {
        "width": 4,
        "lines": [
            "___",
            " |",
            "_/"
        ]
    },
    "k": {
        "width": 5,
        "lines": [
            "_  _",
            "|_/",
            "| \\_"
        ]
    },
    "l": {
        "width": 5,
        "lines": [
            "_",
            "|",
            "|___"
        ]
    },
    "m": {
        "width": 5,
        "lines": [
            "_  _",
            "|\\/|",
            "|  |"
        ]
    },
    "n": {
        "width": 5,
        "lines": [
            "_  _",
            "|\\ |",
            "| \\|"
        ]
    },
    "o": {
        "width": 5,
        "lines": [
            "____",
            "|  |",
            "|__|"
        ]
    },
    "p": {
        "width": 5,
        "lines": [
            "___",
            "|__]",
            "|"
        ]
    },
    "q": {
        "width": 5,
        "lines": [
            "____",
            "|  |",
            "|_\\|"
        ]
    },
    "r": {
        "width": 5,
        "lines": [
            "____",
            "|__/",
            "|  \\"
        ]
    },
    "s": {
        "width": 5,
        "lines": [
            " ___",
            "(__",
            "___)"
        ]
    },
    "t": {
        "width": 5,
        "lines": [
            "___",
            " |",
            " |"
        ]
    },
    "u": {
        "width": 5,
        "lines": [
            "_  _",
            "|  |",
            "|_/|"
        ]
    },
    "v": {
        "width": 5,
        "lines": [
            "_  _",
            "|  |",
            " \\/"
        ]
    },
    "w": {
        "width": 5,
        "lines": [
            "_ _ _",
            "| | |",
            "\\/ \\/"
        ]
    },
    "x": {
        "width": 5,
        "lines": [
            "_  _",
            " \\/",
            "_/\\_"
        ]
    },
    "y": {
        "width": 5,
        "lines": [
            "_   _",
            " \\_/",
            "  |"
        ]
    },
    "z": {
        "width": 5,
        "lines": [
            "___",
            "  /",
            " /__"
        ]
    },
    "1": {
        "width": 5,
        "lines": [
            "  /|",
            "   |",
            "   |"
        ]
    },
    "2": {
        "width": 5,
        "lines": [
            "---.",
            " __/",
            "|___"
        ]
    },
    "3": {
        "width": 5,
        "lines": [
            "---.",
            "___|",
            "___|"
        ]
    },
    "4": {
        "width": 5,
        "lines": [
            " / |",
            "/__|",
            "   |"
        ]
    },
    "5": {
        "width": 5,
        "lines": [
            ".---",
            "|__.",
            "___|"
        ]
    },
    "6": {
        "width": 5,
        "lines": [
            "|",
            "|__.",
            "|__|"
        ]
    },
    "7": {
        "width": 5,
        "lines": [
            "---.",
            "  / ",
            " /  "
        ]
    },
    "8": {
        "width": 5,
        "lines": [
            ".--.",
            "|__|",
            "|__|"
        ]
    },
    "9": {
        "width": 5,
        "lines": [
            ".--.",
            "|__|",
            "   |"
        ]
    },
    "0": {
        "width": 5,
        "lines": [
            ".--.",
            "|  |",
            "|__|"
        ]
    }

};
var charArray = ('abcdefghijklmnopqrstuvwxyz1234567890').split("");
console.log(charArray);
function text2ASCII(str_input) {
    var strArray = str_input.split("");
    var ar = [[' * '], [' * '], [' * ']];
    strArray.forEach(function (char) {
        if (charArray.indexOf(char) === -1) {
            console.log('not:'+char);
            ar[0].push((' ').repeat(4));
            ar[1].push((' ').repeat(4));
            ar[2].push((' ').repeat(4));
        } else {
            for (var i = 0; i < 3; i++) {
                var str2 = chars[char].lines[i];
                ar[i].push(str2 + (' ').repeat(chars[char].width - str2.length));
            }
        }
    });
    console.log(ar);
    var maxLen = 0;
    ar.forEach(function (arr) {
        if (arr.length > maxLen) maxLen = ar.length;
    });
    var str = ('*').repeat(150);
    return "/**" + str + "\n" + ([ar[0].join(""), ar[1].join(""), ar[2].join("")]).join("\n") + "\n *\n " + str + "*/";
}




