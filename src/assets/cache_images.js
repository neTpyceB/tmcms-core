/*
 * Preload images, for ex. for hover effect or in gallery next photos.
 *
 * @Usage:
 * // Put two images in queue for loading
 * CacheImages.store('/path/to/image.png');
 * CacheImages.store('/path/to/image.jpg');
 *
 * // Load them
 * CacheImages.now();
 */

var CacheImages = {
    container: null,
    storage: [],
    stored: [],
    store: function(src) {
        var s = this.storage, i = 0, so = s.length;
        for (; i < so; i++) {
            if (s[i] === src) return;
        }
        this.storage[so] = src;
        this.stored[so] = false;
    },
    now: function() {
        if (!this.container) {
            var div = document.createElement('div');
            div.style.position = 'absolute';
            div.style.top = '-9999px';
            div.style.left = '-9999px';
            this.container = document.body.appendChild(div);
            div = document.createElement('div');
            div.style.position = 'relative';
            div.id = 'image_load_cache';
            this.container = this.container.appendChild(div);
        }
        var i = 0, so = this.storage.length, img;
        for (; i < so; i++) {
            if (this.stored[i]) continue;
            img = document.createElement('img');
            img.style.position = 'absolute';
            img.style.top = 0;
            img.style.left = 0;
            img.src = this.storage[i];
            this.container.appendChild(img);
            this.stored[i] = true;
        }
    }
};