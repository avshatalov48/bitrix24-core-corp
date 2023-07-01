/**
 * @module crm/entity-document/product
 */
jn.define('crm/entity-document/product', (require, exports, module) => {
	const { EntityDocumentProductGrid } = require('crm/entity-document/product/product-grid');
	const { EntityDocumentProductModelLoader } = require('crm/entity-document/product/product-model-loader');

	module.exports = {
		EntityDocumentProductGrid,
		EntityDocumentProductModelLoader,
	};
});
