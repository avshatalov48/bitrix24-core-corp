(() => {

	/**
	 * @class StoreProductBarcodeScannerAdapter
	 */
	class StoreProductBarcodeScannerAdapter
	{
		constructor({root, onSelect})
		{
			/** @type StoreProductList */
			this.root = root;

			const emptyCallback = () => {};
			this.onSelect = onSelect || emptyCallback;
		}

		openScanner()
		{
			BarcodeScannerWidget.open({
				layoutProps: {
					onBarcodeScanned: (scanResult, scanner) => {
						scanner.stopScanning();
						const startScanning = () => {
							setTimeout(() => {
								scanner.startScanning();
							}, 1000)
						};

						const barcode = scanResult.value;

						BX.ajax.runAction(
							'mobile.catalog.storeDocumentProduct.findProductByBarCode',
							{data: {barcode}}
						)
							.then((response) => {
								if (response.data && response.data.id)
								{
									this.onSelect(response.data.id, barcode);
									scanner.close();
								}
								else
								{
									ErrorNotifier
										.showError(BX.message('CSPL_PRODUCT_NOT_FOUND_BY_BARCODE'))
										.then(() => startScanning());
								}
							})
							.catch((response) => {
								ErrorNotifier
									.showErrors(response.errors)
									.then(() => startScanning());
							});
					},
				}
			});
		}
	}

	jnexport(StoreProductBarcodeScannerAdapter);

})();