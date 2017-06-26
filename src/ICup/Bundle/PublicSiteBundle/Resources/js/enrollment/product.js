/*
 ------------------------------------
 PRODUCT
 ------------------------------------
 */
function product(category, metrics) {
    this.sku = category.id; // product code (SKU = stock keeping unit)
    this.name = category.name;
    this.description = Translator.trans('CATEGORY', {}, 'tournament')+" "+category.name+" - "+category.classification_translated;
    this.gender = category.gender;
    this.classification = category.classification;
    this.age = category.age;
    if (category.classification == 'U') {
        this.year = metrics.yearofbirth;
    }
    this.price = metrics.pricemetrics.fee + metrics.pricemetrics.deposit;
    this.deposit = metrics.pricemetrics.deposit;
    this.currency = metrics.pricemetrics.currency;
}
