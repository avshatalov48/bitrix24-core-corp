/**
 * @module catalog/store/product-list/model
 */
jn.define('catalog/store/product-list/model', (require, exports, module) => {

	const { ProductRow } = require('layout/ui/product-grid/model/product-row');

	/**
	 * @class StoreProductRow
	 */
	class StoreProductRow extends ProductRow
	{
		getId()
		{
			return this.props.id;
		}

		getProductName()
		{
			return this.getField('name');
		}

		getProductId()
		{
			return this.getField('productId');
		}

		getPhotos()
		{
			return Object.values(this.getField('galleryInfo')).map(picture => picture?.previewUrl);
		}

		getSkuTree()
		{
			return this.getField('skuTree');
		}

		getAmount()
		{
			return this.getField('amount');
		}

		getStoreFrom()
		{
			return this.getField('storeFrom');
		}

		getStoreFromId()
		{
			return this.getField('storeFromId');
		}

		getStoreTo()
		{
			return this.getField('storeTo');
		}

		getStoreToId()
		{
			return this.getField('storeToId');
		}

		getPurchasePrice()
		{
			return this.getField('price.purchase');
		}

		getSellPrice()
		{
			return this.getField('price.sell');
		}

		getMeasure()
		{
			return this.getField('measure');
		}

		getType()
		{
			return this.getField('type');
		}

		isNew()
		{
			return this.getField('isNew');
		}
	}

	module.exports = { StoreProductRow };

});
