(() => {

	include('InAppNotifier');

	const PUSH_NOTIFICATION_TYPE = 'CATALOG_BARCODE_SCANNER';
	const SHOW_NOTIFICATION_DURATION = 10000;
	const OPEN_BARCODE_SCANNER_COMMAND = 'OpenBarcodeScanner';

	/**
	 * @class CatalogBarcodeScannerBackground
	 */
	class CatalogBarcodeScannerBackground
	{
		constructor()
		{
			this.barcodeNotificationId = null;
			this.isBarcodeNotificationVisible = false;

			this.handlePullEvents = this.handlePullEvents.bind(this);

			BX.addCustomEvent('onPullEvent-catalog', this.handlePullEvents);
		}

		handlePullEvents(command, params, extra, moduleId)
		{
			switch (command)
			{
				case OPEN_BARCODE_SCANNER_COMMAND:
					this.suggestOpenBarcodeScanner(params);
					break;
				default:
					break;
			}
		}

		suggestOpenBarcodeScanner(params)
		{
 			setTimeout(() => {
 				this.isBarcodeNotificationVisible = false;
 			}, SHOW_NOTIFICATION_DURATION);

			this.setBarcodeNotificationId(params.id);

			if (!this.isBarcodeNotificationVisible)
			{
				jnComponent.getState('catalog:catalog.barcode.scanner')
					.then(() => {
						BX.postComponentEvent(
							'CatalogBarcodeScanner::onSessionIdChanged',
							[params.id],
						);
					})
					.catch(() => {
						this.showNotification();
					})
				;
			}
		}

		showNotification()
		{
			InAppNotifier.setHandler(() => this.openBarcodeScanner());
			InAppNotifier.showNotification({
				title: BX.message('CATALOG_BACKGROUND_BARCODE_SCAN_TITLE'),
				backgroundColor: '#E6000000',
				message: BX.message('CATALOG_BACKGROUND_BARCODE_SCAN_TEXT'),
				time: SHOW_NOTIFICATION_DURATION / 1000,
			});
			this.isBarcodeNotificationVisible = true;
		}

		openBarcodeScanner()
		{
			this.isBarcodeNotificationVisible = false;

			ComponentHelper.openLayout({
				name: 'catalog:catalog.barcode.scanner',
				canOpenInDefault: true,
				object: 'layout',
				componentParams: {
					ID: this.barcodeNotificationId,
				},
				widgetParams: BarcodeScannerWidget.getWidgetParams(),
			});
		}

		setBarcodeNotificationId(id)
		{
			this.barcodeNotificationId = id;
			return this;
		}
	}

	this.CatalogBarcodeScannerBackground = new CatalogBarcodeScannerBackground();

	/*
	* System push processing
	*/
	this.onAppActive = () => {
		const push = Application.getLastNotification();
		if (push && push.hasOwnProperty('params'))
		{
			let pushParams = JSON.parse(push.params);
			if (pushParams && pushParams.hasOwnProperty('TYPE') && pushParams.TYPE === PUSH_NOTIFICATION_TYPE)
			{
				this.CatalogBarcodeScannerBackground.setBarcodeNotificationId(
					pushParams.ID || ''
				);
				this.CatalogBarcodeScannerBackground.openBarcodeScanner();
			}
		}
	};
	BX.addCustomEvent('onAppActive', this.onAppActive.bind(this));
	this.onAppActive();
})();
