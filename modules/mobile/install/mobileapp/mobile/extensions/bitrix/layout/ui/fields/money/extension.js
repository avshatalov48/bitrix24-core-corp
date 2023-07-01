/**
 * @module layout/ui/fields/money
 */
jn.define('layout/ui/fields/money', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');
	const { CombinedField } = require('layout/ui/fields/combined');
	const { NumberField, NumberPrecision } = require('layout/ui/fields/number');
	const { SelectField } = require('layout/ui/fields/select');
	const { StringField } = require('layout/ui/fields/string');
	const { Type } = require('type');

	/**
	 * @class MoneyField
	 */
	class MoneyField extends BaseField
	{
		constructor(props)
		{
			super(props);

			/** @type {BaseField} */
			this.fieldRef = null;

			this.customAmountClickHandler = null;

			this.bindRef = this.bindRef.bind(this);
			this.onAmountClick = this.handleAmountClick.bind(this);
			this.onChange = this.handleOnChange.bind(this);
			this.handleRenderLockedAmountIcon = this.renderLockedAmountIcon.bind(this);
		}

		hasNestedFields()
		{
			return true;
		}

		showTitle()
		{
			return this.isReadOnly();
		}

		getTitleText()
		{
			if (this.isReadOnly())
			{
				const { readOnlyTitle } = this.getConfig();

				if (readOnlyTitle !== '')
				{
					return readOnlyTitle;
				}
			}

			return super.getTitleText();
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectionOnFocus: BX.prop.getBoolean(config, 'selectionOnFocus', false),
				amountReadOnly: BX.prop.getBoolean(config, 'amountReadOnly', false),
				amountLocked: BX.prop.getBoolean(config, 'amountLocked', false),
				formatAmount: BX.prop.getBoolean(config, 'formatAmount', false),
				largeFont: BX.prop.getBoolean(config, 'largeFont', false),
				currencyReadOnly: BX.prop.getBoolean(config, 'currencyReadOnly', false),
				readOnlyTitle: BX.prop.getString(config, 'readOnlyTitle', ''),
				currencyTitle: (
					BX.prop.getString(config, 'currencyTitle', '')
					|| BX.message('MOBILE_LAYOUT_UI_FIELDS_MONEY_CURRENCY_TITLE')
				),
				defaultCurrency: BX.prop.getString(config, 'defaultCurrency', ''),
			};
		}

		getFormatConfig()
		{
			const config = this.getConfig();
			const formats = Money.create(this.getValue()).format;
			const thousandsSeparator = jnComponent.convertHtmlEntities(formats.THOUSANDS_SEP);

			return {
				useGroupSeparator: BX.prop.getBoolean(config, 'useGroupSeparator', true),
				groupSize: BX.prop.getNumber(config, 'groupSize', 3),
				groupSeparator: Boolean(thousandsSeparator) ? thousandsSeparator : ' ',
				precision: formats.DECIMALS,
				decimalSeparator: formats.DEC_POINT,
				hideZero: formats.HIDE_ZERO === 'Y',
			};
		}

		getDefaultCurrency()
		{
			return this.getConfig().defaultCurrency;
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', false);
		}

		validate(checkFocusOut = true)
		{
			if (this.fieldRef)
			{
				return this.fieldRef.validate(checkFocusOut);
			}

			return true;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			if (!this.isReadOnly())
			{
				const hasErrorMessage = this.hasErrorMessage();

				styles.wrapper.paddingTop = 0;
				styles.wrapper.paddingBottom = hasErrorMessage ? 5 : 0;
				styles.readOnlyWrapper.paddingTop = 0;
				styles.readOnlyWrapper.paddingBottom = hasErrorMessage ? 5 : 0;
			}

			styles.title.fontWeight = '500';

			return styles;
		}

		isLockedAmount()
		{
			return this.getConfig().amountLocked;
		}

		isLargeFont()
		{
			return this.getConfig().largeFont;
		}

		getAmountFontSize()
		{
			if (this.isLargeFont())
			{
				return 21;
			}

			return 16;
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			const value = this.getValue();
			if (value.amount === '')
			{
				value.amount = 0;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				MoneyView({
					money: Money.create(value),
					renderAmount: (formattedAmount) => Text({
						testId: `${this.testId}-money-view-amount`,
						text: formattedAmount,
						style: {
							...this.styles.value,
							flex: null,
							fontWeight: '500',
							fontSize: this.getAmountFontSize(),
						},
					}),
					renderCurrency: (formattedCurrency) => Text({
						testId: `${this.testId}-money-view-currency`,
						text: formattedCurrency,
						style: {
							...this.styles.value,
							flex: null,
							color: '#828b95',
							fontSize: this.getAmountFontSize(),
						},
					}),
				}),
				this.renderLockedAmountIcon(),
			);
		}

		renderLockedAmountIcon()
		{
			if (this.isLockedAmount())
			{
				return Image({
					testId: `${this.testId}-is-locked-amount`,
					style: {
						marginLeft: 5,
						width: 39,
						height: 25,
						alignSelf: 'flex-end',
					},
					svg: {
						content: autoOpportunitySvg,
					},
				});
			}

			return null;
		}

		prepareSingleValue(value)
		{
			if (!BX.type.isPlainObject(value))
			{
				value = { amount: value };
			}

			return { ...value, currency: this.normalizeCurrency(value.currency) };
		}

		isEmptyValue(value)
		{
			return !value.hasOwnProperty('amount') || Type.isNil(value.amount);
		}

		setCustomAmountClickHandler(handler)
		{
			this.customAmountClickHandler = handler;

			return this;
		}

		handleOnChange(value)
		{
			let amount = value.amount;
			if (value.amount !== '')
			{
				amount = BX.type.isNumber(Number(value.amount)) ? String(value.amount) : '';
			}

			this.handleChange({ ...value, amount });
		}

		handleAmountClick()
		{
			if (this.props.onContentClick)
			{
				this.props.onContentClick();
			}
			else if (this.customAmountClickHandler)
			{
				this.customAmountClickHandler();
			}
			else if (this.getConfig().amountReadOnly)
			{
				dialogs.showSnackbar(
					{
						title: BX.message('MOBILE_LAYOUT_UI_FIELDS_MONEY_IS_AUTOMATIC'),
						showCloseButton: true,
						id: 'moneyIsReadonly',
						backgroundColor: '#000000',
						textColor: '#ffffff',
						hideOnTap: true,
						autoHide: true,
					},
					() => {
					},
				);
			}
		}

		focus()
		{
			if (this.fieldRef)
			{
				return this.fieldRef.focus();
			}

			return Promise.reject();
		}

		/**
		 * @returns {CombinedField}
		 */
		renderEditableContent()
		{
			const {
				amountReadOnly,
				formatAmount,
				selectionOnFocus,
				currencyTitle,
				currencyReadOnly,
				multiple,
			} = this.getConfig();
			const value = this.getValue();
			const useFormattedAmount = amountReadOnly && formatAmount;
			const primaryField = useFormattedAmount ? StringField : NumberField;
			const amountLocked = this.isLockedAmount();
			const { renderAdditionalContent, props } = this.props;

			return CombinedField({
				ref: this.bindRef,
				testId: this.testId,
				value,
				focus: this.state.focus,
				onChange: this.onChange,
				renderAdditionalContent: !multiple && renderAdditionalContent,
				parent: this,
				config: {
					primaryField: {
						...this.props,
						id: 'amount',
						renderField: primaryField,
						readOnly: this.isReadOnly() || amountReadOnly || amountLocked,
						disabled: this.isDisabled() || amountReadOnly || amountLocked,
						config: {
							...this.getConfig(),
							type: NumberPrecision.NUMBER,
							...this.getFormatConfig(),
							selectionOnFocus: selectionOnFocus,
							deepMergeStyles: {
								value: {
									fontWeight: '500',
									fontSize: this.getAmountFontSize(),
									flex: null,
								},
								editableValue: {
									color: amountLocked && '#828b95',
									fontWeight: '500',
									fontSize: this.getAmountFontSize(),
									flex: 1,
								},
							},
						},
						onContentClick: this.onAmountClick,
						renderAdditionalContent: this.handleRenderLockedAmountIcon,
						hasHiddenEmptyView: false,
					},
					secondaryField: {
						...props,
						id: 'currency',
						renderField: SelectField,
						title: currencyTitle,
						readOnly: this.isReadOnly() || currencyReadOnly,
						disabled: this.isDisabled(),
						required: true,
						showRequired: false,
						config: {
							items: this.getCurrenciesList(),
						},
						hasHiddenEmptyView: false,
					},
					deepMergeStyles: {
						secondaryFieldWrapper: {
							flex: 0,
						},
					},
				},
			});
		}

		bindRef(ref)
		{
			this.fieldRef = ref;
		}

		normalizeCurrency(currency)
		{
			if (!currency)
			{
				currency = this.getDefaultCurrency();
			}

			const currencyList = this.getCurrenciesList();
			if (currencyList.length)
			{
				if (currency)
				{
					const currencyExist = currencyList.find((item) => item.value === currency);
					if (!currencyExist)
					{
						currency = '';
					}
				}
				else
				{
					currency = currencyList[0].value;
				}
			}

			return currency;
		}

		getCurrenciesList()
		{
			const availableCurrencies = BX.prop.getArray(this.getConfig(), 'availableCurrencies', null);
			const needFilterCurrencies = Array.isArray(availableCurrencies);

			return Object.keys(Money.formats)
				.filter(currencyId =>
					needFilterCurrencies
						? availableCurrencies.includes(currencyId)
						: true,
				)
				.map(currencyId => {
					const money = new Money({ amount: 0, currency: currencyId });

					return {
						value: currencyId,
						selectedName: money.formattedCurrency,
						name: money.currencyName,
					};
				})
				;
		}
	}

	const autoOpportunitySvg = '<svg width="39" height="25" viewBox="0 0 39 25" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="6" width="36" height="14" rx="2" fill="#A8ADB4"/><path d="M19.1602 16H20.4141L18.4531 10.3633H17.0664L15.1094 16H16.3203L16.75 14.6328H18.7305L19.1602 16ZM17.7266 11.4414H17.7539L18.4766 13.75H17.0039L17.7266 11.4414ZM22.0547 10.3633H20.875V14.0078C20.875 15.25 21.793 16.0977 23.2383 16.0977C24.6797 16.0977 25.5977 15.25 25.5977 14.0078V10.3633H24.418V13.8906C24.418 14.6133 23.9922 15.0859 23.2383 15.0859C22.4805 15.0859 22.0547 14.6133 22.0547 13.8906V10.3633ZM29.0664 16V11.3359H30.6992V10.3633H26.2578V11.3359H27.8867V16H29.0664ZM33.582 10.2656C31.9102 10.2656 30.8633 11.3867 30.8633 13.1836C30.8633 14.9766 31.9102 16.0977 33.582 16.0977C35.25 16.0977 36.3008 14.9766 36.3008 13.1836C36.3008 11.3867 35.25 10.2656 33.582 10.2656ZM33.582 11.25C34.5039 11.25 35.0938 12 35.0938 13.1836C35.0938 14.3633 34.5039 15.1094 33.582 15.1094C32.6562 15.1094 32.0664 14.3633 32.0664 13.1836C32.0664 12 32.6602 11.25 33.582 11.25Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M5.74928 9.49874C5.83372 9.2976 6.03057 9.16675 6.24871 9.16675H12.207C12.5062 9.16675 12.7487 9.40926 12.7487 9.70841V10.223C12.7487 10.5222 12.5062 10.7647 12.207 10.7647H12.18C11.8958 10.7647 11.6654 10.5343 11.6654 10.2501V10.2501H7.53892L10.0895 12.8499C10.2962 13.0606 10.2962 13.3979 10.0895 13.6086L7.53892 16.2084H11.6654V16.0595C11.6654 15.7603 11.9079 15.5178 12.207 15.5178V15.5178C12.5062 15.5178 12.7487 15.7603 12.7487 16.0595V16.7501C12.7487 17.0492 12.5062 17.2917 12.207 17.2917H6.24871C6.03057 17.2917 5.83372 17.1609 5.74928 16.9598C5.66483 16.7586 5.70928 16.5265 5.86205 16.3707L8.94401 13.2292L5.86205 10.0877C5.70928 9.93203 5.66483 9.69987 5.74928 9.49874Z" fill="white"/></svg>';

	module.exports = {
		MoneyType: 'money',
		MoneyField: (props) => new MoneyField(props),
		MoneyFieldClass: MoneyField,
	};

});
