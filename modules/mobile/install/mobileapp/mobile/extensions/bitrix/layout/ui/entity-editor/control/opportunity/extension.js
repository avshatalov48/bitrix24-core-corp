(() => {

	const isManualPriceFieldName = 'IS_MANUAL_OPPORTUNITY';

	/**
	 * @class EntityEditorOpportunityField
	 */
	class EntityEditorOpportunityField extends EntityEditorField
	{
		constructor(props)
		{
			super(props);

			this.state.amountLocked = this.guessAmountIsLocked();

			/** @type {Fields.MoneyInput} */
			this.fieldRef = null;
		}

		initialize(id, uid, type, settings)
		{
			type = 'money';

			super.initialize(id, uid, type, settings);
		}

		initializeStateFromModel()
		{
			super.initializeStateFromModel();

			this.state.amountLocked = this.guessAmountIsLocked();
		}

		guessAmountIsLocked()
		{
			if (!this.model.hasField(isManualPriceFieldName))
			{
				return false;
			}

			const isManualPrice = this.model.getField(isManualPriceFieldName, 'N');
			if (isManualPrice === 'Y')
			{
				return false;
			}

			const controller = (
				BX.prop.getArray(this.editor.settings, 'controllers', [])
					.find((controller) => controller.name === 'PRODUCT_LIST')
			);
			if (controller && controller.config)
			{
				const productSummaryFieldName = BX.prop.getString(controller.config, 'productSummaryFieldName', '');
				if (productSummaryFieldName)
				{
					const productSummary = this.model.getField(productSummaryFieldName, null);
					if (productSummary)
					{
						const { count } = productSummary;

						return count > 0;
					}
				}
			}

			return false;
		}

		getValueFromModel(defaultValue = null)
		{
			if (!this.model)
			{
				return null;
			}

			const amountField = this.schemeElement.data.amount;
			const amount = this.model.getField(
				amountField,
				BX.prop.getNumber(defaultValue, 'amount', 0),
			);

			const currencyField = this.schemeElement.data.currency.name;
			const currency = this.model.getField(
				currencyField,
				BX.prop.getString(defaultValue, 'currency', ''),
			);

			return { amount, currency };
		}

		prepareConfig()
		{
			const config = super.prepareConfig();

			return {
				...config,
				// ToDo it feels like it saves in db settings and should be in model
				amountReadOnly: BX.prop.get(config, 'amountReadOnly', false),
				amountLocked: this.state.amountLocked,
				largeFont: true,
				formatAmount: true,
				currencyReadOnly: false,
			};
		}

		getValuesToSave()
		{
			if (!this.isEditable())
			{
				return {};
			}

			const amountField = this.schemeElement.data.amount;
			const currencyField = this.schemeElement.data.currency.name;

			let amount = '';
			let currency = '';

			if (this.state.value)
			{
				amount = this.state.value.amount;
				currency = this.state.value.currency;
			}

			return {
				[amountField]: amount,
				[currencyField]: currency,
			};
		}

		setCustomAmountClickHandler(handler)
		{
			if (this.fieldRef)
			{
				this.fieldRef.setCustomAmountClickHandler(handler);
			}
		}

		isAmountLocked()
		{
			return this.state.amountLocked;
		}

		lockAmount()
		{
			if (this.state.amountLocked !== true)
			{
				return new Promise((resolve) => {
					this.setState({
						amountLocked: true,
						mode: this.parent.getMode(),
					}, resolve);
				});
			}

			return Promise.resolve();
		}

		unlockAmount()
		{
			if (this.state.amountLocked !== false)
			{
				return new Promise((resolve) => {
					this.setState({ amountLocked: false }, resolve);
				});
			}

			return Promise.resolve();
		}

		unlockAmountAndFocus()
		{
			return new Promise((resolve) => {
				this.setState({
					amountLocked: false,
					mode: BX.UI.EntityEditorMode.edit,
				}, () => this.focusField().then(resolve));
			});
		}
	}

	jnexport(EntityEditorOpportunityField);
})();
