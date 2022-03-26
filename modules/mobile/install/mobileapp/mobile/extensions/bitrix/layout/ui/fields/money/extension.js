(() => {
	class ReadonlyMoneyField extends Fields.StringInput
	{
		isEmptyValue(value)
		{
			return (
				!value.hasOwnProperty('amount')
				|| !value.hasOwnProperty('currency')
			);
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			const value = this.props.value;
			if (value.amount === '')
			{
				value.amount = 0;
			}

			return MoneyView({
				money: Money.create(value),
				renderAmount: (formattedAmount) => Text({
					text: formattedAmount,
					style: {
						...this.styles.value,
						flex: 'none',
						fontWeight: '500',
					}
				}),
				renderCurrency: (formattedCurrency) => Text({
					text: formattedCurrency,
					style: {
						...this.styles.value,
						flex: 'none',
						color: '#82888f',
					}
				}),
			});
		}
	}

	/**
	 * @class MoneyField
	 */
	class MoneyField extends Fields.BaseField
	{
		constructor(props)
		{
			super({
				...props,
				showTitle: false,
				realShowTitle: BX.prop.getBoolean(props, 'showTitle', true),
				required: false
			});

			this.state = this.state || {};

			/** @type {Fields.BaseField} */
			this.fieldRef = null;
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectionOnFocus: BX.prop.getBoolean(config, 'selectionOnFocus', false),
				amountReadOnly: BX.prop.getBoolean(config, 'amountReadOnly', false),
				formatAmount: BX.prop.getBoolean(config, 'formatAmount', false),
				currencyReadOnly: BX.prop.getBoolean(config, 'currencyReadOnly', false),
				currencyTitle: (
					BX.prop.getString(config, 'currencyTitle', '')
					|| BX.message('MOBILE_LAYOUT_UI_FIELDS_MONEY_CURRENCY_TITLE')
				)
			};
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();
			styles.wrapper.paddingTop = 0;
			styles.wrapper.paddingBottom = this.hasErrorMessage() ? 5 : 0;
			styles.readOnlyWrapper.paddingTop =  0;
			styles.readOnlyWrapper.paddingBottom = this.hasErrorMessage() ? 5 : 0;

			return styles;
		}

		renderReadOnlyContent()
		{
			return new ReadonlyMoneyField({
				...this.props,
				showTitle: this.props.realShowTitle,
				readOnly: true,
				ref: (ref) => this.fieldRef = ref,
			})
		}

		renderEditableContent()
		{
			let amount = '';
			let currency = '';
			let precision = 2;
			if (this.props.value !== null)
			{
				amount = this.normalizeAmount(this.props.value.amount);
				currency = this.props.value.currency;
				precision = Number(Money.create({amount: 0, currency}).format.DECIMALS);
			}
			const currenciesList = this.getCurrenciesList();

			const config = this.getConfig();
			const amountFieldType = (
				config.amountReadOnly && config.formatAmount
					? FieldFactory.Type.STRING
					: FieldFactory.Type.NUMBER
			);

			if (config.amountReadOnly && config.formatAmount)
			{
				amount = Money.create({amount, currency}).formattedAmount;
			}

			return FieldFactory.create(FieldFactory.Type.COMBINED, {
				primaryField: FieldFactory.create(
					amountFieldType,
					{
						...this.props,
						ref: this.props.amountRef,
						showTitle: this.props.realShowTitle,
						readOnly: config.amountReadOnly,
						value: amount,
						onChange: (amount) => {
							if (this.props.onChange)
							{
								amount = this.normalizeAmount(amount);
								const currency = this.props.value
									? this.props.value.currency
									: currenciesList.length ? currenciesList[0].value : ''
								;
								this.handleChange({amount, currency});
							}
						},
						config: {
							type: Fields.NumberField.Types.DOUBLE,
							precision: precision,
							selectionOnFocus: config.selectionOnFocus
						},
						onContentClick: (
							config.amountReadOnly
								? () => dialogs.showSnackbar({
										title: BX.message('MOBILE_LAYOUT_UI_FIELDS_MONEY_IS_AUTOMATIC'),
										showCloseButton: true,
										id: 'moneyIsReadonly',
										backgroundColor: "#000000",
										textColor: "#ffffff",
										hideOnTap: true,
										autoHide: true
									},
									() => {
								}
							)
							: null
						)
					}
				),
				secondaryField: FieldFactory.create(
					FieldFactory.Type.SELECT,
					{
						...this.props,
						ref: this.props.currencyRef,
						showTitle: this.props.realShowTitle,
						title: config.currencyTitle,
						readOnly: config.currencyReadOnly,
						value: currency,
						items: currenciesList,
						required: true,
						showRequired: false,
						onChange: (currency) => {
							const amount = this.props.value ? this.props.value.amount : '';
							this.handleChange({amount, currency});
						}
					}
				),
				config: {
					styles: {
						combinedContainer: {
							flexWrap: 'wrap',
							justifyContent: 'center',
							alignItems: 'center',
							flexDirection: 'row',
							width: '100%',
						}
					}
				},
				ref: (ref) => this.fieldRef = ref,
			});
		}

		normalizeAmount(amount)
		{
			if (isNaN(String(amount).replace(',', '.')) || amount === null || amount === undefined)
			{
				amount = '';
			}

			return String(amount).replace(',', '.');
		}

		getCurrenciesList()
		{
			const availableCurrencies = BX.prop.getArray(this.getConfig(), 'availableCurrencies', null);
			const needFilterCurrencies = Array.isArray(availableCurrencies);

			return Object.keys(Money.formats)
				.filter(currencyId =>
					needFilterCurrencies
						? availableCurrencies.includes(currencyId)
						: true
				)
				.map(currencyId => {
					const money = new Money({amount: 0, currency: currencyId});

					return {
						value: currencyId,
						selectedName: money.formattedCurrency,
						name: money.currencyName,
					};
				})
			;
		}

		focus()
		{
			if (this.fieldRef)
			{
				this.fieldRef.focus()
			}
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.MoneyField = MoneyField;
})();
