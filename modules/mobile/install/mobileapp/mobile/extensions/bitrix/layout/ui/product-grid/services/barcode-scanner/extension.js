/**
 * @module layout/ui/product-grid/services/barcode-scanner
 */
jn.define('layout/ui/product-grid/services/barcode-scanner', (require, exports, module) => {

	const { Loc } = require('loc');

	/**
	 * @class BarcodeScanner
	 */
	class BarcodeScanner
	{
		constructor({ onSelect })
		{
			const emptyCallback = () => {};
			this.onSelect = onSelect || emptyCallback;
		}

		open()
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

						// @todo separate controller
						BX.ajax.runAction(
							'catalogmobile.StoreDocumentProduct.findProductByBarCode',
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
										.showError(Loc.getMessage('PRODUCT_GRID_SERVICE_BARCODE_SCANNER_PRODUCT_NOT_FOUND'))
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

    module.exports = { BarcodeScanner };

});