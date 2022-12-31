/**
 * @typedef {object} SkuSelectorProps
 * @property {object} layout
 * @property {number} selectedVariationId
 * @property {number} quantity
 * @property {string} currencyId
 * @property {object} skuTree
 * @property {string} measureName
 * @property {string} saveButtonCaption
 * @property {function} onSave
 */

/**
 * @typedef {object} SkuSelectorState
 * @property {number} selectedVariationId
 * @property {number} quantity
 * @property {boolean} loading
 */

/**
 * @typedef {object} SkuSelectorVariation
 * @property {string} NAME
 * @property {object[]} GALLERY
 * @property {number} PRICE
 * @property {string} CURRENCY
 * @property {number} PRICE_BEFORE_TAX
 * @property {number} TAX_VALUE
 * @property {number} TAX_RATE
 * @property {number} TAX_INCLUDED
 * @property {boolean} TAX_MODE
 * @property {string|null} TAX_NAME
 * @property {boolean} EMPTY_PRICE
 * @property {string} BARCODE
 */