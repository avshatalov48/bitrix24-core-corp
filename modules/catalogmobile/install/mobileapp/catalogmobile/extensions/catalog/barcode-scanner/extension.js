(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	const pathToExtension = '/bitrix/mobileapp/catalogmobile/extensions/catalog/barcode-scanner/';

	class BarcodeScanner extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
			this.successOverlay = null;
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgSecondary,
					},
				},
				CameraView({
					style: {
						width: '100%',
						height: '100%',
						backgroundColor: AppTheme.colors.bgSecondary,
						borderTopLeftRadius: 12,
						borderTopRightRadius: 12,
					},
					scanTypes: this.getScanTypes(),
					result: (barcode) => {
						BX.ajax.runAction(
							'catalog.analytics.sendAnalyticsLabel',
							{ analyticsLabel: 'catalog:barcodeScanned' },
						);
						if (typeof this.props.onBarcodeScanned !== 'function')
						{
							this.stopScanning();

							return;
						}

						this.props.onBarcodeScanned(barcode, this);
					},
					error: (error) => console.error(error),
					ref: (ref) => {
						this.cameraRef = ref;
					},
				}),
				this.renderSuccessOverlay(),
			);
		}

		close()
		{
			this.layoutWidget.close();
		}

		startScanning()
		{
			this.cameraRef.setScanEnabled(true);
		}

		stopScanning()
		{
			this.cameraRef.setScanEnabled(false);
		}

		showSuccessOverlay(options = {})
		{
			return this.successOverlay.show(options);
		}

		hideSuccessOverlay()
		{
			return this.successOverlay.hide();
		}

		renderSuccessOverlay()
		{
			return new UI.Overlay({
				type: UI.Overlay.Types.SUCCESS,
				ref: (view) => {
					this.successOverlay = view;
				},
			});
		}

		getScanTypes()
		{
			if (this.props.hasOwnProperty('scanTypes') && Array.isArray(this.props.scanTypes))
			{
				return this.props.scanTypes;
			}

			return this.getDefaultScanTypes();
		}

		getDefaultScanTypes()
		{
			return [
				'ean_13',
				'pdf_417',
				'code_39',
				'code_93',
				'code_128',
				'ean_8',
				'ean_13',
				'upc_e',
				'itf',
				'aztec',
				'data_matrix',
			];
		}

		setLayoutWidget(layoutWidget)
		{
			this.layoutWidget = layoutWidget;

			return this;
		}
	}

	class BarcodeScannerWidget
	{
		static open(options)
		{
			const {
				widgetTitle,
				layoutProps,
			} = options;

			const widgetParams = BarcodeScannerWidget.getWidgetParams();
			if (widgetTitle)
			{
				widgetParams.title = widgetTitle;
			}

			const parentWidget = options.parentWidget || PageManager;

			parentWidget
				.openWidget('layout', widgetParams)
				.then((layoutWidget) => {
					const barcodeScanner = new BarcodeScanner(layoutProps);

					layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.showComponent(barcodeScanner);

					barcodeScanner.setLayoutWidget(layoutWidget);
				});
		}

		static getWidgetParams()
		{
			return {
				title: BX.message('CATALOG_BARCODE_SCANNER_TITLE2'),
				backdrop: {
					mediumPositionPercent: 70,
					horizontalSwipeAllowed: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
					hideNavigationBar: true,
				},
			};
		}
	}

	this.BarcodeScanner = BarcodeScanner;
	this.BarcodeScannerWidget = BarcodeScannerWidget;
})();
