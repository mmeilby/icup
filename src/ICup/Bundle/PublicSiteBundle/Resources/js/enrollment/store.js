/*
 ------------------------------------
 STORE (contains the products)
 ------------------------------------
 */
function store() {
    this.products = [];
}
store.prototype.getProduct = function (sku) {
    for (var i = 0; i < this.products.length; i++) {
        if (this.products[i].sku == sku)
            return this.products[i];
    }
    return null;
}
store.prototype.setProducts = function (categories, categoryMetrics) {
    this.products = [];
    for (var i = 0; i < categories.length; i++) {
        this.products.push(new product(categories[i], categoryMetrics[i]));
    }
    return null;
}
