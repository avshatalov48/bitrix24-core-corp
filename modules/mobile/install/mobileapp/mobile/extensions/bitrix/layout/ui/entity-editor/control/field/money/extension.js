(() => {
	/**
	 * @class EntityEditorMoneyField
	 */
	class EntityEditorMoneyField extends EntityEditorField
	{
		getValueFromModel(defaultValue = null)
		{
			if (!this.model)
			{
				return null;
			}
			const amountField = this.schemeElement.data.amount;
			const currencyField = this.schemeElement.data.currency.name;

			const amount = this.model.getField(
				amountField,
				(defaultValue && defaultValue.amount) ? defaultValue.amount : ''
			);
			const currency = this.model.getField(
				currencyField,
				(defaultValue && defaultValue.currency) ? defaultValue.currency : ''
			);

			return {amount, currency};
		}

		prepareConfig()
		{
			return {
				...super.prepareConfig(),
				amountReadOnly: true,
				currencyReadOnly: false,
			};
		}

		getValue()
		{
			return this.state.value;
		}

		getValuesToSave()
		{
			const amountField = this.schemeElement.data.amount;
			const currencyField = this.schemeElement.data.currency.name;

			let amount = '';
			let currency = '';
			if (this.state.value)
			{
				amount = this.state.value.amount;
				currency = this.state.value.currency;
			}

			const result = {};
			result[amountField] = amount;
			result[currencyField] = currency;

			return result;
		}
	}

	jnexport(EntityEditorMoneyField)
})();
