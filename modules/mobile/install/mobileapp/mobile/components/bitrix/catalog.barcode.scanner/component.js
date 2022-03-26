(() => {
	/**
	 * @class CatalogBarcodeScannerComponent
	 */
	class CatalogBarcodeScannerComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.id = BX.componentParameters.get('ID', '');
			BX.addCustomEvent('CatalogBarcodeScanner::onSessionIdChanged', id => this.id = id);
		}

		render()
		{
			const closeOnSuccessfulScan = BX.componentParameters.get('CLOSE_ON_SCANNED', true);
			const restartScanningTimeout = BX.componentParameters.get('RESTART_SCANNING_TIMEOUT', 2000);

			return View(
				{},
				new BarcodeScanner({
					onBarcodeScanned: (barcode, scanner) => {
						const startScanning = () => {
							setTimeout(() => {
								scanner.startScanning();
							}, restartScanningTimeout)
						};
						scanner.stopScanning();

						BX.ajax.runAction(
							'mobile.catalog.barcodescanner.sendBarcodeScannedEvent',
							{data: {id: this.id, barcode: barcode.value}}
						)
							.then((response) => {
								if (response.data && closeOnSuccessfulScan)
								{
									scanner
										.showSuccessOverlay()
										.then(() => {
											scanner.hideSuccessOverlay();
										})
										.then(() => {
											startScanning();
										});
								}
								else
								{
									startScanning();
								}
							})
							.catch((response) => {
								startScanning();
							});
					},
				})
			);
		}

		componentWillUnmount()
		{
			BX.postComponentEvent('Catalog:BarcodeScannerComponent:onClose');
		}
	}

	BX.onViewLoaded(() => {layout.showComponent(new CatalogBarcodeScannerComponent());});
})();
