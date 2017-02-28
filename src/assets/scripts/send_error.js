var register_js_error = {
    stack: [],
    ini: function () {
        window.onerror = register_js_error.register;
        register_js_error.signOnClick();
    },
    signOnClick: function () {
        if (document.body) document.body.onclick = register_js_error.clicked;
        else setTimeout(register_js_error.signOnClick, 25);
    },
    clicked: function (e) {
        var o;
        if (!e) {
            e = window.event;
            o = e.srcElement;
        } else o = e.target;
        if (!o) return;
        if (register_js_error.stack.length > 10) register_js_error.stack.shift();
        register_js_error.stack.push(o.id === '' ? o : o.id);
    },
    normalize: function () {
        var i = 0, s = register_js_error.stack, so = s.length, res = [], o, tmp, k, e;
        for (; i < so; i++) {
            if (typeof s[i] === 'string') {
                res[i] = '#' + s[i];
                continue;
            }
            o = s[i];
            tmp = [];
            while (o && o.parentNode && o.tagName !== 'BODY') {
                if (o.tagName === 'TBODY') {
                    o = o.parentNode;
                    continue;
                }
                for (k = 0, e = o; e = e.previousSibling; e.tagName ? k++ : null);
                tmp.push(o.tagName + (k ? '-' + k : ''));
                o = o.parentNode;
            }
            res[i] = tmp.join('>');
        }
        return res;
    },
    register: function (msg, url, line) {
        if (typeof msg === 'undefined' || typeof url === 'undefined' || typeof line === 'undefined') return;
        setTimeout(
            function() {
                if (document.body) {
                    document.body.appendChild(document.createElement("script")).src = '/-/api/send_js_error/?msg=' + encodeURIComponent(msg) + '&url=' + encodeURIComponent(url) + '&line=' + encodeURIComponent(line) + '&stack=' + encodeURIComponent(register_js_error.normalize());
                }
            },
            999
        );
        window.onerror = null;
    }
};