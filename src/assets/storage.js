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