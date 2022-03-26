(() =>
{
	class StoreCatalogProductPricesStep extends CatalogProductWizardStep
	{
		prepareFields()
		{
			this.clearFields();

			const documentCurrency = this.entity.get('DOCUMENT_CURRENCY');

			this.setDefaultValues({
				'PURCHASING_PRICE': {
					amount: '',
					currency: documentCurrency,
				},
				'BASE_PRICE': {
					amount: '',
					currency: documentCurrency,
				},
			});

			this.addField(
				'PURCHASING_PRICE',
				FieldFactory.Type.MONEY,
				BX.message('WIZARD_FIELD_PRODUCT_PURCHASING_PRICE'),
				this.entity.get('PURCHASING_PRICE'),
				{
					config: {
						selectionOnFocus: true,
						currencyReadOnly: true,
					},
				}
			);

			this.addField(
				'BASE_PRICE',
				FieldFactory.Type.MONEY,
				BX.message('WIZARD_FIELD_PRODUCT_BASE_PRICE'),
				this.entity.get('BASE_PRICE'),
				{
					config: {
						selectionOnFocus: true,
						currencyReadOnly: true,
					},
				}
			);
		}

		renderFooter()
		{
			return View({
					style: CatalogProductWizardStepStyles.footer.container,
				},
				Text({
					style: CatalogProductWizardStepStyles.footer.text,
					text: BX.message('WIZARD_STEP_FOOTER_TEXT_PRICE')
				})
			);
		}

		onMoveToNextStep()
		{
			return super.onMoveToNextStep()
				.then(() => this.entity.save())
				;
		}
	}

	this.StoreCatalogProductPricesStep = StoreCatalogProductPricesStep;
})();
