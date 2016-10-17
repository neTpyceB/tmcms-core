var JsLoader = function() {
    this.data = [];
    this.callback = false;
    this.data2load_so = 0;
    this.max_load_attempts = 3;
};
JsLoader.prototype.setAllLoadedCallback = function(callback) {
    if (typeof callback !== 'function') return false;
    this.callback = callback;
    return true;
};
JsLoader.prototype.add = function(url, async) {
    var data = this.data, i = data.length - 1;
    for (; i >= 0; i--) {
        if (data[i].url === url) return true;
    }
    this.data[data.length] = {
        url: url,
        async: !!async,
        attempts: 0
    };
    this.data2load_so++;
    return true;
};
JsLoader.prototype.loadById = function(id) {
    if (!this.data[id]) return false;
    this.data[id].attempts++;
    if (this.data[id].attempts > this.max_load_attempts) return false;

    var self = this,
        script = document.createElement('script');
    script.async = this.data[id].async;
    script.onerror = function(e) {
        if (id === false) return;
        setTimeout(function(){
            self.loadById(id);
        }, 99);
    };
    script.onload = function() {
        self.loaded(self);
    };
    script.onreadystatechange= function () {
        if (this.readyState === 'complete') self.loaded(self);
    }
    script.src = this.data[id].url;

    document.getElementsByTagName('head')[0].appendChild(script);

    delete script;

};
JsLoader.prototype.loadAll = function() {
    var data = this.data, i = 0, so = data.length;
    for (; i < so; i++) this.loadById(i);
    delete head;
};
JsLoader.prototype.loaded = function(self) {
    self.data2load_so--;
    if (self.data2load_so <= 0 && self.callback) (self.callback)();
};
JsLoader.prototype._getIdByUrlPart = function(data, url) {
    var url_l = url.length, i = data.length - 1, data_url_l;
    for (; i >= 0; i--) {
        data_url_l = data[i].url.length;
        if (data_url_l < url_l && url.substr(url_l - data_url_l) === data[i].url) return i;
    }
    return false;
};