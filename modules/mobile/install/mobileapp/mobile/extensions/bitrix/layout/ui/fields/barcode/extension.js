/**
 * @module layout/ui/fields/barcode
 */
jn.define('layout/ui/fields/barcode', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { StringFieldClass } = require('layout/ui/fields/string');

	/**
	 * @class BarcodeField
	 */
	class BarcodeField extends StringFieldClass
	{
		renderEditableContent()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				super.renderEditableContent(),
			);
		}

		renderEditIcon()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				View({
					style: {
						width: 1,
						backgroundColor: AppTheme.colors.bgSeparatorPrimary,
						marginRight: 11.5,
					},
				}),
				View(
					{
						style: {
							paddingTop: 10,
							paddingBottom: 10,
							paddingLeft: 9,
							paddingRight: 9,
						},
						onClick: () => {
							BarcodeScannerWidget.open({
								layoutProps: {
									onBarcodeScanned: this.handleOnBarCodeScanned.bind(this),
								},
								parentWidget: this.getParentWidget(),
							});
						},
					},
					Image({
						style: {
							width: 18,
							height: 18,
						},
						svg: {
							content: `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.78866 0.71875H0.0996094V17.2761H1.78866V0.71875ZM17.9001 0.718791H16.2111V17.2762H17.9001V0.718791ZM9.83665 0.71875H11.3265V17.2761H9.83665V0.71875ZM9.23647 0.718791H7.54742V17.2762H9.23647V0.718791ZM2.5338 0.718791H5.54707V17.2762H2.5338V0.718791ZM14.6215 0.718791H12.2371V17.2762H14.6215V0.718791Z" fill="${AppTheme.colors.base3}"/></svg>`,
						},
						resizeMode: 'center',
					}),
				),
			);
		}

		handleOnBarCodeScanned(barcode, scanner)
		{
			scanner.stopScanning();
			this.changeText(barcode.value);
			scanner.close();
			this.setFocus();
		}

		shouldShowEditIcon()
		{
			return true;
		}
	}

	module.exports = {
		BarcodeType: 'barcode',
		BarcodeField: (props) => new BarcodeField(props),
	};
});
