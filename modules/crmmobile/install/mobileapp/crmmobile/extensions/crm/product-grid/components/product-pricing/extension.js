/**
 * @module crm/product-grid/components/product-pricing
 */
jn.define('crm/product-grid/components/product-pricing', (require, exports, module) => {
	const { Loc } = require('loc');
	const { mergeImmutable } = require('utils/object');
	const { debounce } = require('utils/function');
	const {
		ProductGridMoneyField,
		ProductGridNumberField,
	} = require('layout/ui/product-grid/components/string-field');
	const { DiscountPrice } = require('layout/ui/product-grid/components/discount-price');
	const { notify } = require('layout/ui/product-grid/components/hint');
	const { ProductRow } = require('crm/product-grid/model');
	const { Haptics } = require('haptics');
	const { DiscountType } = require('crm/product-calculator');

	const tap = (fn) => (...args) => {
		setTimeout(() => Haptics.impactMedium(), 0);
		fn(...args);
	};

	class ProductPricing extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		/**
		 * @returns {ProductRow}
		 */
		get productRow()
		{
			return this.props.productRow;
		}

		render()
		{
			return View(
				{},
				Row(
					{},
					this.renderPrice(),
					this.renderQuantity(),
				),
				Row(
					{
						style: {
							marginBottom: 0,
						},
					},
					this.renderDiscountField(),
					this.renderTotalSum(),
				),
				this.renderDiscountValue(),
				this.showTax && this.renderTaxes(),
			);
		}

		renderPrice()
		{
			const price = this.productRow.getBasePrice();
			const currency = this.productRow.getCurrency();
			const money = new Money({ amount: price, currency });
			const moneyStub = new Money({ amount: 0, currency });

			const handleChange = (field) => {
				const newVal = this.normalizeMoneyFieldValue(field.value);
				if (newVal !== price)
				{
					this.onChangePrice(newVal);
				}
			};

			return new ProductGridMoneyField({
				disabled: !this.isPriceFieldEditable(),
				value: money.amount,
				currency: money.currency,
				placeholder: moneyStub.formattedAmount,
				label: `${Loc.getMessage('PRODUCT_GRID_CONTROL_PRICING_PRICE')}, ${money.formattedCurrency}`,
				onChange: debounce((field) => {
					if (field.value === '')
					{
						return;
					}
					handleChange(field);
				}, 300),
				onBlur: (field) => {
					if (field.value === '')
					{
						handleChange(field);
					}
				},
				onClick: () => this.notifyPriceDisabled(),
				testId: 'productGridPriceField',
			});
		}

		renderQuantity()
		{
			const value = this.productRow.getQuantity();
			const disabled = !this.props.editable;

			const moneyFormat = Money.create({
				amount: 0,
				currency: this.productRow.getCurrency(),
			}).format;
			const groupSeparator = jnComponent.convertHtmlEntities(moneyFormat.THOUSANDS_SEP);

			const handleChange = (field) => {
				const newVal = field.value;
				if (newVal !== value)
				{
					this.onChangeQuantity(newVal);
				}
			};

			return new ProductGridNumberField({
				disabled,
				value,
				groupSize: 3,
				groupSeparator: groupSeparator || ' ',
				decimalSeparator: moneyFormat.DEC_POINT,
				placeholder: '0',
				useIncrement: true,
				useDecrement: true,
				min: 1,
				step: 1,
				label: this.productRow.getMeasureName(),
				labelAlign: 'center',
				textAlign: 'center',
				onChange: debounce((field) => {
					if (field.value === '')
					{
						return;
					}
					handleChange(field);
				}, 300),
				onBlur: (field) => {
					if (field.value === '')
					{
						handleChange(field);
					}
				},
				testId: 'productGridQuantityField',
			});
		}

		renderDiscountField()
		{
			const currency = this.productRow.getCurrency();
			const discountType = this.productRow.getDiscountType();
			const value = discountType === DiscountType.MONETARY
				? this.productRow.getDiscountSum()
				: this.productRow.getDiscountRate();

			const moneyStub = new Money({ amount: 0, currency });
			const disabled = !this.isDiscountFieldEditable();

			const handleChange = (field) => {
				const newVal = this.normalizeMoneyFieldValue(field.value);
				if (newVal !== value)
				{
					this.onChangeDiscountValue(newVal);
				}
			};

			return new ProductGridMoneyField({
				value,
				currency,
				disabled,
				placeholder: moneyStub.formattedAmount,
				keyboardType: 'decimal-pad',
				label: Loc.getMessage('PRODUCT_GRID_CONTROL_PRICING_DISCOUNT'),
				onChange: debounce((field) => {
					if (field.value === '')
					{
						return;
					}
					handleChange(field);
				}, 300),
				onBlur: (field) => {
					if (field.value === '')
					{
						handleChange(field);
					}
				},
				rightBlock: (field) => DiscountTypeSwitch({
					disabled,
					text: discountType === DiscountType.PERCENTAGE ? '%' : moneyStub.formattedCurrency,
					onClick: tap(() => {
						const nextDiscountType = discountType === DiscountType.PERCENTAGE
							? DiscountType.MONETARY
							: DiscountType.PERCENTAGE;

						const nextDiscountValue = field.isFocused ? this.normalizeMoneyFieldValue(field.value) : false;

						this.onChangeDiscountType(nextDiscountType, nextDiscountValue);
					}),
				}),
				testId: 'productGridDiscountField',
			});
		}

		renderDiscountValue()
		{
			const currency = this.productRow.getCurrency();
			const discountRow = this.productRow.getDiscountRow();
			const oldPriceValue = this.productRow.getMaxPrice();
			const quantity = this.productRow.getQuantity();
			const oldPriceSum = oldPriceValue * quantity;

			if (discountRow === 0)
			{
				return null;
			}

			const discount = new Money({ amount: discountRow, currency });
			const oldPrice = new Money({ amount: oldPriceSum, currency });

			return Row(
				{
					style: {
						marginTop: 6,
						marginBottom: 0,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-end',
						},
					},
					DiscountPrice({
						oldPrice,
						discount,
					}),
				),
			);
		}

		renderTotalSum()
		{
			const amount = this.productRow.getSum();
			const currency = this.productRow.getCurrency();
			const money = new Money({ amount, currency });
			const moneyStub = new Money({ amount: 0, currency });

			const handleChange = (field) => {
				const val = this.normalizeMoneyFieldValue(field.value);
				if (val !== amount)
				{
					this.onChangeSum(val);
				}
			};

			return new ProductGridMoneyField({
				value: money.amount,
				currency: money.currency,
				placeholder: moneyStub.formattedAmount,
				label: `${Loc.getMessage('PRODUCT_GRID_CONTROL_PRICING_SUM_EDITABLE')}, ${money.formattedCurrency}`,
				labelAlign: 'right',
				textAlign: 'right',
				disabled: !this.isDiscountFieldEditable(),
				onChange: debounce((field) => {
					if (field.value === '')
					{
						return;
					}
					handleChange(field);
				}, 300),
				onBlur: (field) => {
					if (field.value === '')
					{
						handleChange(field);
					}
				},
				testId: 'productGridTotalSumField',
			});
		}

		renderTaxes()
		{
			const taxPercent = this.productRow.getTaxRate();

			if (taxPercent === null || taxPercent === 0 || this.productRow.isTaxMode())
			{
				return null;
			}

			const taxIncluded = this.productRow.isTaxIncluded();
			const taxIncludedMessageCode = taxIncluded
				? 'PRODUCT_GRID_CONTROL_PRICING_TAX_INCLUDED'
				: 'PRODUCT_GRID_CONTROL_PRICING_TAX_NOT_INCLUDED';
			const taxIncludedMessage = Loc.getMessage(taxIncludedMessageCode);

			const taxPercentMessage = Loc.getMessage('PRODUCT_GRID_CONTROL_PRICING_TAX')
				.replace('#PERCENT#', taxPercent);

			const amount = this.productRow.getTaxSum();
			const currency = this.productRow.getCurrency();
			const taxValue = new Money({ amount, currency });

			const taxValueMessage = `${taxPercentMessage}, ${taxValue.formatted}`;

			const style = {
				fontSize: 12,
				color: '#a8adb4',
				textAlign: 'right',
			};

			return Row(
				{
					style: {
						marginTop: 4,
						marginBottom: 0,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-end',
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						Text({
							style,
							text: taxValueMessage,
						}),
						Text({
							style,
							text: taxIncludedMessage,
						}),
					),
				),
			);
		}

		onChangePrice(newValue)
		{
			if (this.props.onChangePrice)
			{
				this.props.onChangePrice(newValue);
			}
		}

		onChangeSum(newValue)
		{
			if (this.props.onChangeSum)
			{
				this.props.onChangeSum(newValue);
			}
		}

		onChangeQuantity(newValue)
		{
			if (this.props.onChangeQuantity)
			{
				this.props.onChangeQuantity(newValue);
			}
		}

		onChangeDiscountValue(newValue)
		{
			if (this.props.onChangeDiscountValue)
			{
				this.props.onChangeDiscountValue(newValue);
			}
		}

		onChangeDiscountType(discountType, discountValue)
		{
			if (this.props.onChangeDiscountType)
			{
				this.props.onChangeDiscountType(discountType, discountValue);
			}
		}

		onToggleDiscount()
		{
			if (this.props.onToggleDiscount)
			{
				const newValue = !this.props.discountVisible;
				this.props.onToggleDiscount(newValue);
			}
		}

		get showTax()
		{
			return BX.prop.getBoolean(this.props, 'showTax', true);
		}

		notifyPriceDisabled()
		{
			if (!this.productRow.isPriceEditable())
			{
				const title = Loc.getMessage('PRODUCT_GRID_CONTROL_PRICING_FIELD_CHANGE_NOT_PERMITTED_TITLE');
				const message = Loc.getMessage('PRODUCT_GRID_CONTROL_PRICING_FIELD_CHANGE_NOT_PERMITTED_BODY');
				const seconds = 5;

				notify({ title, message, seconds });
			}
		}

		/**
		 * @return {boolean}
		 */
		isPriceFieldEditable()
		{
			return this.isEntityEditable() && this.productRow.isPriceEditable();
		}

		/**
		 * @return {boolean}
		 */
		isDiscountFieldEditable()
		{
			return this.isEntityEditable() && this.productRow.isDiscountEditable();
		}

		/**
		 * @return {boolean}
		 */
		isEntityEditable()
		{
			return this.props.editable;
		}

		/**
		 * @param {string} raw
		 * @returns {number}
		 */
		normalizeMoneyFieldValue(raw)
		{
			const val = String(raw).replace(',', '.').trim();
			return Number(val);
		}
	}

	function Row(options, ...columns)
	{
		const horizontalGap = 8;
		const verticalGap = 8;

		const children = columns.map((columnContent, index, arr) => {
			const maxIndex = arr.length - 1;
			const style = {
				flexGrow: 1,
				flexBasis: 0,
				marginLeft: index === 0 ? 0 : horizontalGap,
				marginRight: index === maxIndex ? 0 : horizontalGap,
			};
			return View({ style }, columnContent);
		});

		const defaultOptions = {
			style: {
				flexDirection: 'row',
				justifyContent: 'space-between',
				marginBottom: verticalGap,
			},
		};

		return View(
			mergeImmutable(defaultOptions, options),
			...children,
		);
	}

	function DiscountTypeSwitch(props)
	{
		return View(
			{
				style: {
					flexDirection: 'row',
				},
				onClick: () => (props.disabled ? false : props.onClick()),
			},
			View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						paddingLeft: 4,
						paddingRight: 4,
					},
				},
				Text({
					text: String(props.text),
					style: {
						color: '#828b95',
						fontSize: 16,
					},
				}),
			),
			!props.disabled && View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
					},
				},
				Image({
					style: {
						width: 8,
						height: 5,
					},
					svg: {
						content: '<svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.8344 0.0751953L4.57087 2.33872L4.00061 2.90015L3.44117 2.33872L1.17764 0.0751953L0.378906 0.873929L4.00599 4.50101L7.63307 0.873929L6.8344 0.0751953Z" fill="#A8ADB4"/></svg>',
					},
				}),
			),
		);
	}

	module.exports = { ProductPricing };
});
