// Check if browser is Internet Explorer
function isIE() {
    return (window.navigator.appName == 'Microsoft Internet Explorer');
}

function popup(url, name, width, height, x, y, attr, content, insertHTML) {
    /*
     Usage
     All of the paramaters are not obligatory. It means that you can pass none of them and the popup is going to open anyway.

     @url [STRING] - URI of the document to open.
     @name [STRING] - Name of the new window, which can be used in target for A and FORM tags.
     @width [STRING] - Width of the window.
     @height [STRING] - Height of the window.
     @x [STRING] - Location of the window for x axis.
     @y [STRING] - Location of the window for y axis.
     @attr [STRING] - attributes of the window:
     copyhistory   Копировать историю просмотра текущего окна.
     dependent     Создать окно, зависимое от родительского окна. Зависимые окна закрываются при закрытии родительского окна и не показываются в панели задач Windows.
     directories   Показывать панель каталогов обозревателя.
     height        Высота окна в пикселях.
     location      Показывать адресную строку обозревателя.
     menubar       Показывать меню обозревателя.
     resizable     Пользователь может изменять размеры окна.
     screenX       Расстояние в пикселях от левого края экрана по горизонтали.
     screenY       Расстояние в пикселях от верхнего края экрана по вертикали.
     left          Расстояние в пикселях от левого края экрана по горизонтали.
     top           Расстояние в пикселях от верхнего края экрана по вертикали.
     scrollbars    Показывать полосы прокрутки окна.
     status        Показывать строку состояния обозревателя.
     toolbar       Показывать панель кнопок обозревателя.
     width         Ширина окна в пикселях.
     @content [STRING] - Some HTML content that will be written to the window. Pass some HTML or an empty string or "auto" for auto filling with HTML. Additionaly view @insertHTML
     @insertHTML [STRING] - if you pass auto for @content, than this will be placed between BODY tags.
     */


    if (empty(url)) var url = '';
    if (!isset(name)) var name = '';
    if (empty(width)) var width = '100';
    else {
        if (width == 'fullscreen') var width = screen.availWidth;
        else {
            width = width.toString();
            if (width.indexOf('%') != -1) var width = (Math.round(parseInt(width) * screen.availWidth / 100)).toString();
        }
    }

    if (empty(height)) var height = '100';
    else {
        if (height === 'fullscreen') var height = screen.availHeight;
        else {
            height = height.toString();
            if (height.indexOf('%') != -1) var height = (Math.round(parseInt(height) * screen.availHeight / 100)).toString();
        }
    }

    if (empty(x) && width !== 'fullscreen') var x = (Math.round(screen.availWidth / 2 - parseInt(width) / 2)).toString();
    else if (width === 'fullscreen') var x = '0';

    if (empty(y) && height !== 'fullscreen') var y = (Math.round(screen.availHeight / 2 - parseInt(height) / 2)).toString();
    else if (height === 'fullscreen') var y = '0';

    if (!isset(insertHTML)) var insertHTML = '';

    if (empty(attr)) {
        var attr = 'dependent=1,width=' + width + ',height=' + height;
        if (x != '') attr += ',screenX=' + x + ',left=' + x;
        if (y != '') attr += ',screenY=' + y + ',top=' + y;
    } else {
        if (!isset(attr)) var attr = '';
        if (x != '' && attr.indexOf('left=') === -1) attr += (attr != '' ? ',' : '') + 'left=' + x;
        if (x != '' && attr.indexOf('screenX=') === -1) attr += (attr != '' ? ',' : '') + 'screenX=' + x;
        if (y != '' && attr.indexOf('top=') === -1) attr += (attr != '' ? ',' : '') + 'top=' + y;
        if (y != '' && attr.indexOf('screenY=') === -1) attr += (attr != '' ? ',' : '') + 'screenY=' + y;
        if (attr.indexOf('width=') === -1) attr += (attr != '' ? ',' : '') + 'width=' + width;
        if (attr.indexOf('height=') === -1) attr += (attr != '' ? ',' : '') + 'height=' + height;
    }

    if (empty(content)) var content = '';
    else {
        if (content === 'auto') var content = '<html><head><style>BODY{overflow:hidden;padding:0;margin:0;}</style><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta http-equiv="imagetoolbar" content="no" /></head><body onLoad="window.moveTo(' + x + ', ' + y + ');self.focus()" bgcolor="#fff">%content%</body></html>';
    }

    var win = window.open(url, name, attr);
    if (content != '') {
        if (!isIE()) win.document.open();
        win.document.write(content.replace('%content%', insertHTML));
        win.document.close();
    }
    return win;
}

function toggleAll(id_prefix, id_postfix, start_from) {
    if (!isset(id_prefix)) return;
    if (!isset(id_postfix)) var id_postfix = '';
    if (!isset(start_from)) var start_from = 0;
    else start_from = parseInt(start_from);

    var o;

    for (var i = start_from; true; i++) {
        o = document.getElementById(id_prefix + i + id_postfix);
        if (o) {
            if (o.style.display == 'none') o.style.display = 'block';
            else o.style.display = 'none';
        } else break;
    }
}

function toggle(id, focus) {
    if (typeof(id) == 'string') var o = document.getElementById(id);
    else var o = id;

    if (o) {
        if (o.style.display == 'none') {
            o.style.display = '';
            if (isset(focus)) {
                var focusO = document.getElementById(focus);
                if (focusO) focusO.focus();
            }
        } else o.style.display = 'none';
    }
    return id;
}

function toggleOn(id) {
    if (typeof(id) == 'string') var o = document.getElementById(id);
    else var o = id;
    if (o) o.style.display = '';
    return id;
}

function toggleOff(id) {
    if (typeof(id) == 'string') var o = document.getElementById(id);
    else var o = id;
    if (o) o.style.display = 'none';
    return id;
}

function isset(v) {
    return (typeof(v) == 'undefined' ? false : true);
}

// For CMS HTMLgen
var HTMLGen = {
    settings: {countInterval: 3000, hideHelpers: true},
    objects: {},

    register: {
        textarea: function (id, backup_hash, helper) {
            if (!document.getElementById(id)) return;

            if (helper) HTMLGen.helpers.create(id, backup_hash, helper);
        },
        text: function (id, backup_hash, helper) {

            HTMLGen.register.textarea(id, backup_hash, helper);
        }
    },
    helpers: {
        create: function (id, backup_hash, helper) {
            helper = decodeURIComponent(helper).replace(/\+/gm, ' ');
            document.getElementById(id).parentNode.innerHTML = '<div style="position:relative">'
            + document.getElementById(id).parentNode.innerHTML
            + '<div style="visibility: hidden" class="cms_hint">' + helper + '</div></div>';
            var o = document.getElementById(id);
            HTMLGen.objects[id] = o;
            if (backup_hash != '') {
                HTMLGen.storage.register(id, o, backup_hash);
            }

            HTMLGen.objects['help_' + id] = o.parentNode.lastChild;
            addEvent('focus', o, 'HTMLGen.helpers.show("' + id + '")');
            addEvent('blur', o, 'HTMLGen.helpers.hide("' + id + '")');

            HTMLGen.objects['cntr_' + id] = document.getElementById('cntr_' + id);
            addEvent('change', o, 'HTMLGen.count("' + id + '")');
            addEvent('keyup', o, 'HTMLGen.count("' + id + '")');
            setInterval('HTMLGen.count("' + id + '")', HTMLGen.settings.countInterval);
            HTMLGen.count(id);
        },
        show: function (id) {
            if (!HTMLGen.storage.hint_blocked(id)) {

            }
            HTMLGen.objects['help_' + id].style.visibility = '';
        },
        hide: function (id) {
            if (!HTMLGen.storage.hint_blocked(id))
                HTMLGen.objects['help_' + id].style.visibility = 'hidden';
        }
    },
    show_restore: function (id) {
        document.getElementById(id + '_restore').style.visibility = 'visible';
        HTMLGen.helpers.show(id);
    },
    count: function (id) {
        if (!HTMLGen.objects['cntr_' + id] || !HTMLGen.objects[id]) {
            return;
        }
        HTMLGen.objects['cntr_' + id].innerHTML = HTMLGen.objects[id].value.length;
        if($(HTMLGen.objects[id]).data('max')){
            if($(HTMLGen.objects[id]).data('max') < HTMLGen.objects[id].value.length){
                $(HTMLGen.objects['cntr_' + id]).parent().addClass('danger-hint');
            }else{
                $(HTMLGen.objects['cntr_' + id]).parent().removeClass('danger-hint');
            }
        }
        if($(HTMLGen.objects[id]).data('min')){
            if($(HTMLGen.objects[id]).data('min') > HTMLGen.objects[id].value.length){
                $(HTMLGen.objects['cntr_' + id]).parent().addClass('warning-hint');
            }else{
                $(HTMLGen.objects['cntr_' + id]).parent().removeClass('warning-hint');
            }
        }
    },
    storage: {
        objects: {},
        object_to_restore: {},

        store: false,
        interval: 5000,
        expired: 43200, // seconds
        storage_prefix: 'cms',

        init: function (form_uid) {
            try {
                HTMLGen.storage.store = new Storage(HTMLGen.storage.storage_prefix + form_uid);
                $(window).bind('beforeunload', HTMLGen.storage.save);
                HTMLGen.storage.load();
                HTMLGen.storage.save(true);
            } catch (e) {
            }
        },
        register: function (id, o, backup_hash) {
            HTMLGen.storage.objects[id] = {
                'object': o,
                'md5': backup_hash
            };
        },
        startSaver: function () {
            setTimeout('HTMLGen.storage.save(true);', HTMLGen.storage.interval);
        },
        save: function (start_timer) {
            var ts = HTMLGen.storage.getCurrentTs();
            for (var id in HTMLGen.storage.objects) {

                if (HTMLGen.storage.object_to_restore[id]) continue;
                try {
                    var value = ts + HTMLGen.storage.objects[id].md5 + HTMLGen.storage.objects[id].object.value;
                    HTMLGen.storage.store.set(id, value);
                    /*console.log('Set: '+ id + ' '+ value );*/
                } catch (e) {
                }
            }
            if (start_timer === true) HTMLGen.storage.startSaver();
        },
        load: function () {
            var ts = HTMLGen.storage.getCurrentTs();
            for (var id in HTMLGen.storage.objects) {
                var o = HTMLGen.storage.objects[id].object;
                var data = HTMLGen.storage.store.get(id);
                /*console.log('Get: '+ id + ' '+ data);*/
                if (data) {
                    var first_hash = data.substr(10, 32);
                    var value = data.substr(42);

                    if (first_hash === HTMLGen.storage.objects[id].md5 && o.value !== value) {
                        HTMLGen.storage.loadRestore(id, value);
                    }
                }
            }
            HTMLGen.storage.clearOld();
        },
        hide: function (el_id) {
            var restor_o = document.getElementById(el_id + '_restore');
            restor_o.parentNode.style.display = 'none';
            restor_o.parentNode.removeChild(restor_o);
            HTMLGen.storage.unblock(el_id);
        },
        clearOld: function () {
            var keys = HTMLGen.storage.store.getStoragesKeyList();
            var SO = keys.length;
            var storage_prefix_len = HTMLGen.storage.storage_prefix.length;
            var ts = HTMLGen.storage.getCurrentTs();
            var key, value, stored_ts;

            for (var i = 0; i < SO; i++) {
                key = keys[i];
                if (key.substr(0, storage_prefix_len) !== HTMLGen.storage.storage_prefix) continue;
                value = HTMLGen.storage.store.getByFullKey(key);
                stored_ts = parseInt(value.substr(0, 10));
                if (isNaN(stored_ts)) continue;
                if (ts - stored_ts >= HTMLGen.storage.expired) {
                    HTMLGen.storage.store.removeByFullKey(key);
                }
            }
        },
        getRestoVal: function (id) {
            if (!isset(HTMLGen.storage.object_to_restore[id]) || !isset(HTMLGen.storage.objects[id])) return false;
            return HTMLGen.storage.object_to_restore[id];
        },
        restore: function (id) {
            if (!isset(HTMLGen.storage.object_to_restore[id]) || !isset(HTMLGen.storage.objects[id])) return;
            HTMLGen.storage.objects[id].object.value = HTMLGen.storage.object_to_restore[id];
            delete HTMLGen.storage.object_to_restore[id];
            HTMLGen.storage.startSaver();
            document.getElementById(id + '_restore').innerHTML = 'Restored.';
        },
        loadRestore: function (id, old_value) {
            HTMLGen.storage.object_to_restore[id] = old_value;
            HTMLGen.show_restore(id);
        },
        hint_blocked: function (id) {
            return isset(HTMLGen.storage.object_to_restore[id]);
        },
        unblock: function (id) {
            delete HTMLGen.storage.object_to_restore[id];
        },
        getCurrentTs: function () {
            var d = new Date;
            return parseInt(d.getTime() / 1000);
        }
    }
};

function addEvent(ev, o, func, useCapture) {
    // useCapture required only for compability with older versions
    if (!o) return false;

    if (isIE()) o.attachEvent("on" + ev, (typeof(func) == 'string' ? function () {
        eval(func)
    } : func));
    else o.addEventListener(ev, (typeof(func) == 'string' ? function () {
        eval(func)
    } : func), false);
}

function time() {
    return new Date().getTime();
}

function rand(min, max) {
    return Math.floor(Math.random() * ((max + 1) - min)) + min;
}

/* Hint */

var HintHelp = {
    show: function (help_td_id, text, event) {
        var help_td_o = document.getElementById(help_td_id);
        if (!help_td_o) return;
        var helper_o = document.getElementById(help_td_id + '_helper');
        if (!helper_o) {
            var div = document.createElement('DIV');
            div.id = help_td_id + '_helper';
            div.className = 'help_box';
            div.innerHTML = text;
            document.body.appendChild(div);
            helper_o = div;
        }

        var e = window.event || event;
        var mouse_x = e.pageX || e.clientX;
        var mouse_y = e.pageY || e.clientY;
        this.repositioning(helper_o, mouse_x, mouse_y);

        helper_o.style.visibility = 'visible';
    },
    repositioning: function (helper_o, mouse_x, mouse_y) {
        var dif = mouse_x + parseInt(helper_o.offsetWidth, 10);
        var body_width = parseInt(document.body.offsetWidth, 10);
        if (dif > body_width) {
            helper_o.style.left = (body_width - (dif - body_width) - 70) + 'px';
        } else {
            helper_o.style.left = (mouse_x) + 'px';
        }
        helper_o.style.top = (mouse_y + 10) + 'px';
    },
    hide: function (help_td_id) {
        var help_td_o = document.getElementById(help_td_id);
        if (!help_td_o) return;
        if (document.getElementById(help_td_id + '_helper')) {
            document.getElementById(help_td_id + '_helper').style.visibility = 'hidden';
            return;
        }
    }
};

// Local storage
function Storage(name) {
    if (typeof name !== 'string' || name === '') {
        alert('Storage error. Name should not be an empty string.');
        return;
    }
    this.store = localStorage;
    this.name = name;
}
Storage.prototype.key = function (key) {
    return this.name + '_' + key;
};
Storage.prototype.get = function (key) {
    return this.store.getItem(this.key(key));
};
Storage.prototype.set = function (key, value) {
    this.store.setItem(this.key(key), value);
};
// remove by key (key = name + key)
Storage.prototype.remove = function (key) {
    this.store.removeItem(this.key(key));
};
// remove by key (key = key)
Storage.prototype.removeByFullKey = function (key) {
    this.store.removeItem(key);
};
Storage.prototype.getByFullKey = function (key) {
    return this.store.getItem(key);
};
Storage.prototype.getStoragesKeyList = function () {
    var storeSize = this.store.length, keys = [], i = 0;
    for (; i < storeSize; i++) keys.push(this.store.key(i));
    return keys;
};

String.prototype.capitalizeFirstLetter = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

// Arrays
function in_array(v, a) {
    var el;
    for (el in a) {
        if (a[el] == v) return true;
    }
    return false;
}

function array_kick_by_value(data, value) {
    var res = [];
    if (data instanceof Array) {
        var i = 0, so = data.length;
        for (; i < so; i++) {
            if (value != data[i]) res[res.length] = data[i];
        }
    } else {
        var el;
        for (el in data) {
            if (value != data[el]) res[res.length] = data[el];
        }
    }
    return res;
}

function random_number(minVal, maxVal, floatVal) {
    var randVal = minVal + (Math.random() * (maxVal - minVal));
    return typeof floatVal == 'undefined' ? Math.round(randVal) : randVal.toFixed(floatVal);
}

/* Context menu */
var cMenuCloseTime = 999; // Context Menu close time in MS
var cMenu = false, cMenuTimeout;

// Context Menu
if ($) $(document).ready(function () {
    $(document.body).click(closeCMenu);
});

function dropCMenu(e, data, v1, v2, v3, v4, v5) {
    closeCMenu();
    if (!e) e = event;
    var div, i = 0, o, html, so = data.length, url, js;

    div = document.createElement('div');
    div.setAttribute('id', 'cMenu');
    div.className = 'cMenu';
    o = document.body.appendChild(div);
    $('#cMenu').css('left', e.pageX).css('top', e.pageY);
    o.onmouseout = function () {
        cMenu = false;
    };
    o.onmouseover = updateCMenu;

    html = '';
    for (; i < so; i++) {
        url = data[i]['url'];
        js = data[i]['js'];
        if (typeof(v1) !== 'undefined') {
            url = url.replace('%v1%', v1);
            js = js.replace('%v1%', v1);
        }
        if (typeof(v2) !== 'undefined') {
            url = url.replace('%v2%', v2);
            js = js.replace('%v2%', v2);
        }
        if (typeof(v3) !== 'undefined') {
            url = url.replace('%v3%', v3);
            js = js.replace('%v3%', v3);
        }
        if (typeof(v4) !== 'undefined') {
            url = url.replace('%v4%', v4);
            js = js.replace('%v4%', v4);
        }
        if (typeof(v5) !== 'undefined') {
            url = url.replace('%v5%', v5);
            js = js.replace('%v5%', v5);
        }

        html += '<div style="padding: 0 10px 0 10px; margin: 2px 0 2px 0;"><a href="' + url + '" onClick="' + js + '">\n' + data[i]['title'] + '\n</a></div>';
    }
    o.innerHTML = html;
    cMenu = false;
    if (cMenuTimeout) clearTimeout(cMenuTimeout);
    cMenuTimeout = setTimeout('validateCMenu()', cMenuCloseTime);
    html = o = div = null;

    return false;
}

function validateCMenu() {
    if (!cMenu) closeCMenu();
    if (cMenuTimeout) {
        clearTimeout(cMenuTimeout);
        cMenuTimeout = null;
    }
    cMenuTimeout = setTimeout('validateCMenu()', cMenuCloseTime);
}

function updateCMenu() {
    cMenu = true;
}

function closeCMenu() {
    var o = document.getElementById('cMenu');
    if (o) document.body.removeChild(o);
    cMenu = false;
}

var PopupModal = function (options) {
    this.id = null;
    this.window = null;
    this.overlay = null;
    this.options = {
        url: null,
        width: 700,
        height: 500,
        onclose: null,
        result_destination: null,
        result_callback: null
    };

    var $window = $(window),
        $body = $('body'),
        instance = this;

    this.initialize = function () {
        $.extend(instance.options, options);

        return instance;
    };

    this.showLoading = function () {
        $body.append('<div id="cms_show_loading"></div>');

        var showLoadingBlock = $('#cms_show_loading');

        showLoadingBlock.css({
            "display": "block",
            "background": "url(/vendor/devp-eu/tmcms-core/src/assets/images/loading.gif) center no-repeat",
            "background-color": "rgb(0, 0, 0)",
            "position": "fixed",
            "top": "0px",
            "right": "0px",
            "bottom": "0px",
            "left": "0px",
            "opacity": "0.5",
            "z-index": "19998"
        });
    };

    this.hideLoading = function () {
        $('#cms_show_loading').remove();
    };

    this.loadContent = function (url) {
        instance.window.load(url, function() {
            instance.options.url = url;
            instance.hideLoading();
        });
    };

    this.onReturnResult = function (callback) {
        if (typeof callback === 'function') {
            instance.options.result_callback = callback;
        }
    };

    this.resize = function () {
        instance.window.height($window.height() - 100);
        instance.window.width($window.width() - 100);

        instance.window.css('margin-left', -instance.window.width() / 2);
        instance.window.css('margin-top', -instance.window.height() / 2);
    };

    this.close = function () {
        instance.window.remove();
        instance.overlay.remove();

        if (instance.options.onclose == 'reload') {
            location.reload();
        }
    };

    this.setTriggers = function () {
        instance.window.on('popup:load_content', function (event, url) {
            instance.loadContent(url);
        });

        instance.window.on('popup:return_result', function (event, result) {
            if (instance.options.result_destination) {
                $.each(['', '.', '#'], function (index, value) {
                    var destinationObjects = $(value + instance.options.result_destination);

                    if (destinationObjects.length != 0) {
                        destinationObjects.val(result);
                        return false;
                    }
                });
            }

            if (instance.options.result_callback) {
                instance.options.result_callback(result);
            }
        });

        instance.window.on('popup:close', function () {
            instance.close();
        });
    };

    this.show = function () {
        instance.showLoading();

        var activePopups = $('[id="modal-popup_inner"]');

        instance.id = activePopups.size() + 1;

        $body.append('<div id="modal-popup" data-popup-id="' + instance.id + '"></div>');

        var modalOverlay = $('#modal-popup[data-popup-id="' + instance.id + '"]');

        modalOverlay.css({
            "display": "block",
            "position": "fixed",
            "top": "0px",
            "right": "0px",
            "bottom": "0px",
            "left": "0px",
            "background-color": "rgb(0, 0, 0)",
            "opacity": instance.id == 1 ? 0.5 : 0,
            "cursor": "pointer",
            "z-index": 64000 + (activePopups.size() * 1500)
        });

        modalOverlay.click(function () {
            instance.close();
        });

        instance.overlay = modalOverlay;

        if (activePopups.length > 0) {
            instance.options.width = activePopups.width();
            instance.options.height = activePopups.height();
        }

        $body.append('<div id="modal-popup_inner" data-popup-id="' + instance.id + '">' +
                        '<div class="content" style="height: auto; width: auto;"></div>' +
                    '</div>');

        var modalWindow = $('#modal-popup_inner[data-popup-id="' + instance.id + '"]');

        modalWindow.css({
            "display": "block",
            "position": "absolute",
            "left": "50%",
            "top": "50%",
            "z-index": 64000 + ((activePopups.size() + 1) * 1500),
            "opacity": "1",
            "background": "#fff",
            "overflow": "auto",
            "height": instance.options.height + "px",
            "width": instance.options.width + "px",
            "margin-top": -(instance.options.height / 2) + "px",
            "margin-left": -(instance.options.width / 2) + "px"
        });

        instance.window = modalWindow;

        instance.setTriggers();

        if (instance.options.url) {
            instance.loadContent(instance.options.url);
        }

        instance.resize();

        $window.resize(function () {
            instance.resize();
        });
    };

    this.initialize();
};

var popup_modal = {
    resize: function() {
        var $popup = $('#modal-popup_inner');
        var $win = $(window);

        $popup.height($win.height() - 100);
        $popup.width($win.width() - 100);
        $popup.css('margin-left', -$popup.width()  / 2);
        $popup.css('margin-top', -$popup.height()  / 2);
    },
    show: function(url, width, height) {
        this.show_loading();

        var $popup = $('#modal-popup_inner');
        if ($popup.length > 0) { //
            width = $popup.width(); // This makes popup window on every next request keep it's size
            height = $popup.height();
            popup_modal.close();
        } else {
            width = width || 700;
            height = height || 500;
        }

        // Append HTML for for modals
        $('body').append('<div id="modal-popup" style="display: block; position: fixed; top: 0px; right: 0px; bottom: 0px; left: 0px; opacity: 0.5; z-index: 10000; cursor: pointer; background-color: rgb(0, 0, 0);" onclick="popup_modal.close()"></div>');
        $('body').append('<div id="modal-popup_inner" style="overflow: auto; left: 50%; position: absolute; top: 50%; z-index: 10001; opacity: 1; height: '+ height +'px; width: '+ width +'px; margin-top: -'+ (height / 2) +'px; margin-left: -'+ (width / 2) +'px; background: #fff; display: block;"><div class="content" style="height: auto; width: auto;"></div></div>');
        $('#modal-popup_inner').load(url + '&ajax', function() {
            popup_modal.hide_loading();
        });

        popup_modal.resize();
        $(window).resize(function() {
            popup_modal.resize();
        });
    },
    close: function() {
        $('#modal-popup, #modal-popup_inner').remove();
        if (popup_modal.reload_on_close) location.reload();
    },
    result_element: '',
    reload_on_close: false,
    show_loading: function() {
        $('body').append('<div id="cms_show_loading" style="background: url(/vendor/devp-eu/tmcms-core/src/assets/images/loading.gif) center no-repeat; display: block; position: fixed; top: 0px; right: 0px; bottom: 0px; left: 0px; opacity: 0.5; z-index: 19998; background-color: rgb(0, 0, 0);"></div>');
    },
    hide_loading: function() {
        $('#cms_show_loading').remove();
    }
};

// Browser notifications
var cms_notifications = {

    init: function() {
        if (("Notification" in window) && Notification.permission !== "granted") {
            Notification.requestPermission();
        }
    },
    show: function(text, title, url) {
        if (!("Notification" in window)) {
            return;
        }


        if (Notification.permission !== "granted") {
            return Notification.requestPermission();
        }

        if (!title) {
            title = cms_data.site_name;
        }

        if (!text) {
            text = 'Visit page for more info';
        }

        var notification = new Notification(title, {
            icon: '/vendor/devp-eu/tmcms-core/src/assets/images/logo_square.png',
            body: text
        });

        if (url) {
            notification.onclick = function () {
                window.open(url);
                notification.close();
            };
        } else {
            notification.onclick = function () {
                window.focus();
                notification.close();
            };
        }
    }
};


$(function () {
    // Init notifications for browser
    cms_notifications.init();

    // Menu more
    $('.menu_line_table_td var').click(function () {
        $('#menu_more').slideToggle();
    });

    setInterval(function () { // Keep session every 10 minutes
        $.get('?p=home&do=_ajax_keep_admin_session&ajax');
    }, 300000); // 5 minutes

    if (typeof Switchery != 'undefined') {
        // Switch-styled checkboxes
        $('.js-switch-cms').each(function(k, v) {
            var init = new Switchery(v, {
                color: 'green',
                secondaryColor: 'red'
            });
        });
    }

    $('[data-popup-url]').click(function (event) {
        event.preventDefault();

        var $element = $(this);
        var popupModal = new PopupModal({
            url: $element.data('popup-url'),
            width: $element.data('popup-width'),
            height: $element.data('popup-height'),
            onclose: $element.data('popup-onclose'),
            result_destination: $element.data('popup-result-destination')
        });

        popupModal.show();
    });

    $('[data-dialog-modal-url]').click(function(e) {
        e.preventDefault();

        var $el = $(this);
        var url = $el.data('dialog-modal-url');
        popup_modal.result_element = $('#'+ $el.data('result-id'));

        var width = $el.data('dialog-width');
        if (!width) width = 700;
        var height = $el.data('dialog-height');
        if (!height) height = 500;

        // Check max window width and height
        var $window = $(window);
        if ($window.height() < height + 40) height = $window.height() - 40;
        if ($window.width() < width + 40) width = $window.width() - 40;


        popup_modal.show(url, width, height);
    });

    // Datepicker calendars
    var $elms = $('.datepicker');
    if ($elms.length > 0) $elms.datepicker().each(function(k, v) {
        var $el = $(this);
        if ($el.val() == '') {
            var date = new Date();
            $el.val(date.getFullYear() + '-' + ('0' + date.getMonth()).slice(-2) + '-' + ('0' + date.getDate()).slice(-2));
        }
    });

    ajax_toasters.request_new_messages();

    $('.table a[href^="http://"], .table a[href^="https://"]').each(function() {
        $(this).attr('target', '_blank')
    });
});

var ajax_toasters = {
    request_new_messages: function() {
        $.ajax({
            async: true,
            cache: false,
            dataType: "json",
            url: '?p=home&do=_ajax_get_notifications&ajax',
            success: function(data) {
                for (var i in data) {
                    if (Notification && data[i].notify == '0') {
                        cms_notifications.show(data[i].message);
                    } else {
                        switch (parseInt(data[i].notify)) {
                            // 1 - green
                            // 2 - red
                            // 3 - black
                            case 1:
                                toastr.success(data[i].message);
                                break;
                            case 2:
                                toastr.error(data[i].message);
                                break;
                            default:
                                toastr.warning(data[i].message);
                                break;
                        }
                    }
                }
                setTimeout(ajax_toasters.request_new_messages, 15000);
            }
        });
    }
};

// AJAXified checkboxes
function checkbox_by_ajax(element) {
    var $el = $(element);
    var $form = $el.closest('form');
    $el.prop('disabled', true);
    $.post($form.attr('action') + '&ajax', $form.serialize(), function(data) {
        setTimeout(function () {
            $el.prop('disabled', false);

            // Request messages for Toaster
            ajax_toasters.request_new_messages();
        }, 300);
    });
}

var multi_actions = {
    registered_action: {},
    submit: function(element) {
        var $el = $(element);
        var selected = $el.val();
        var table_id = $el.closest('table').attr('id');
        var data = multi_actions.registered_action[table_id];
        var link = data[selected].link;

        var ids = [];
        $('[data-table-id='+ table_id +']:checked').each(function(k, v) {
            ids.push($(this).data('id'));
        });

        if (data[selected].confirm) {
            if (!confirm('Are you sure?')) return;
        }

        location.href = link + '&ids='+ ids;
    }
};

var clipboard_forms = {
    copy_page_forms: function() {
        var texts = [];

        // find all forms and save data
        $("form").each(function(k) {
            texts[k] = $(this).serialize();
        });

        // Show to user
        window.prompt("Copy to clipboard: Ctrl+C, Enter", JSON.stringify(texts));
    },
    paste_page_forms: function() {
        // Ask user
        var texts = window.prompt("Copy from clipboard: Ctrl+V, Enter");

        texts = JSON.parse(texts);

        // Fill forms
        var text;
        var $form;
        $("form").each(function(k, v) {
            text = texts[k];
            $form = $(v);

            $.each(text.split('&'), function (index, elem) {
                var vals = elem.split('=');
                $form.find("[name='" + vals[0] + "']").val(vals[1]);
            });
        });
    }
};