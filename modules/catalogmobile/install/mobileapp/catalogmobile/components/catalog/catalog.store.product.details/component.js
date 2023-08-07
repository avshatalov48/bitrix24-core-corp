(() => {
	const require = (ext) => jn.require(ext);

	const { CatalogStoreProductDetails } = require('catalog/store/product-details');

	BX.onViewLoaded(() => {
		layout.showComponent(new CatalogStoreProductDetails({
			layout: layout,
			product: BX.componentParameters.get('product') || {},
			measures: BX.componentParameters.get('measures') || {},
			permissions: BX.componentParameters.get('permissions') || {},
			catalog: BX.componentParameters.get('catalog') || {},
			document: BX.componentParameters.get('document') || {},
		}));
	});

})();
