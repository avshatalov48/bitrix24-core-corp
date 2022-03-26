(() => {
	/**
	 * @class Fields.BarcodeInput
	 */
	class BarcodeInput extends Fields.StringInput
	{
		renderEditableContent()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row'
					}
				},
				TextField({
					ref: (ref) => this.inputRef = ref,
					style: this.styles.editableValue,
					forcedValue: this.stringify(this.props.value),
					keyboardType: this.getConfig().keyboardType,
					placeholder: this.getPlaceholder(),
					placeholderTextColor: this.styles.textPlaceholder.color,
					onFocus: () => this.setFocus(),
					onBlur: () => this.removeFocus(),
					onChangeText: (text) => this.changeText(text),
					onSubmitEditing: () => this.inputRef.blur()
				}),
				View(
					{
						onClick: () => {
							BarcodeScannerWidget.open({
								layoutProps: {
									onBarcodeScanned: this.handleOnBarCodeScanned.bind(this)
								}
							});
						}
					},
					Text({
						style: this.styles.scanBarcodeText,
						text: this.props.scanBarcodeText || BX.message('FIELDS_INLINE_FIELD_SCAN_BARCODE_TEXT')
					})
				)
            );
		}

		handleOnBarCodeScanned(barcode, scanner)
		{
			scanner.stopScanning();
			this.changeText(barcode.value);
			scanner.close();
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				scanBarcodeText: {
					color: '#333333',
				},
			};
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.BarcodeInput = BarcodeInput;
})();
