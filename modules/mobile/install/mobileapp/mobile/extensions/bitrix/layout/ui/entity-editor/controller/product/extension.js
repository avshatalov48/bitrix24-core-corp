(() => {
	/**
	 * @class EntityEditorProductController
	 */
	class EntityEditorProductController extends EntityEditorBaseController
	{
		initialize()
		{
			this.on(CatalogStoreEvents.ProductList.TotalChanged, this.onChangeProduct.bind(this));
			this.on('EntityEditorField::onChangeState', this.onChangeFieldValue.bind(this));

			this.fieldUpdateInProgress = false;
			this.currency = '';
		}

		loadFromModel()
		{
			this.currency = this.model.getField('CURRENCY', '');
		}

		onChangeProduct(data)
		{
			this.updateTotalSumField(data);
			this.updateProductSummarySection(data);
		}

		onChangeFieldValue(data)
		{
			if (data.fieldName !== 'TOTAL_WITH_CURRENCY')
			{
				return;
			}

			const newCurrency = data.fieldValue ? data.fieldValue.currency : null;
			if (!this.fieldUpdateInProgress && newCurrency && this.currency !== newCurrency)
			{
				this.currency = newCurrency;
				this.emit('EntityEditorProductController::onChangeCurrency', [newCurrency]);
			}
		}

		updateTotalSumField(data)
		{
			this.fieldUpdateInProgress = true;

			const amount = BX.prop.getNumber(data, 'total', 0);
			const field = this.editor.getControlByIdRecursive('TOTAL_WITH_CURRENCY');
			if (field)
			{
				const prevFieldValue = field.getValue();
				const currency = prevFieldValue ? prevFieldValue.currency : '';

				field.setValue({amount, currency})
					.then(() => {
						// @todo fix: EntityEditorField::onChangeState called asynchronously so this callback executed earlier than necessary
						setTimeout(() => {
							this.fieldUpdateInProgress = false;
						}, 100);

					})
				;
			}
			else
			{
				this.fieldUpdateInProgress = false;
			}
		}

		updateProductSummarySection(data)
		{
			const amount = BX.prop.getNumber(data, 'total', 0);
			const items = BX.prop.getArray(data, 'items', []);
			const field = this.editor.getControlByIdRecursive('DOCUMENT_PRODUCTS');
			if (field)
			{
				const currency = this.currency;

				field.setValue({
					count: items.length,
					total: {amount, currency}
				});
			}
		}
	}

	jnexport(EntityEditorProductController)
})();
