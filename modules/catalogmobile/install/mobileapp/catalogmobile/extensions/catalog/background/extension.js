(() => {

	include('InAppNotifier');

	/**
	 * @class CatalogBackgroundWorker
	 */
	class CatalogBackgroundWorker
	{
		constructor()
		{
			this.on(Event.PushFromCatalog, this.executeCommand.bind(this));
		}

		executeCommand(command, params, extra, moduleId)
		{
			switch (command)
			{
				case Command.OpenBarcodeScanner:
					this.suggestOpenBarcodeScanner(params);
					break;

				default:
					break;
			}
		}

		suggestOpenBarcodeScanner(params)
		{
			const oneMinute = 60 * 1000;

			InAppNotifier.setHandler(params => this.openBarcodeScanner(params));

			InAppNotifier.showNotification({
				title: BX.message('CATALOG_BACKGROUND_BARCODE_SCAN_TITLE'),
				backgroundColor: '#E6000000',
				message: BX.message('CATALOG_BACKGROUND_BARCODE_SCAN_TEXT'),
				time: oneMinute,
				data: params,
			});
		}

		openBarcodeScanner(params)
		{
			ComponentHelper.openLayout({
				name: 'catalog:catalog.barcode.scanner',
				canOpenInDefault: true,
				object: 'layout',
				componentParams: {
					ID: params.id,
				},
				widgetParams: BarcodeScannerWidget.getWidgetParams(),
			});
		}

		on(eventName, callback)
		{
			BX.addCustomEvent(eventName, callback);
			return this;
		}

		emit(eventName, args)
		{
			BX.postComponentEvent(eventName, args);
		}
	}

	const Event = {
		PushFromCatalog: 'onPullEvent-catalog',
	};

	const Command = {
		OpenBarcodeScanner: 'OpenBarcodeScanner',
	};

	this.CatalogBackgroundWorker = new CatalogBackgroundWorker();

	/*
	* Push processing
	*/
	const push = Application.getLastNotification();
	if (push && push.hasOwnProperty('params'))
	{
		let pushParams = JSON.parse(push.params);
		if (pushParams && pushParams.hasOwnProperty('TYPE') && pushParams.TYPE === 'CATALOG_BARCODE_SCANNER')
		{
			this.CatalogBackgroundWorker.openBarcodeScanner({id: pushParams.ID || ''});
		}
	}
})();
