/**
 * @module layout/ui/product-grid/components/string-field/money
 */
jn.define('layout/ui/product-grid/components/string-field/money', (require, exports, module) => {

	const { ProductGridStringField } = require('layout/ui/product-grid/components/string-field/string');
	const { stringify } = require('utils/string');

	/**
	 * @class ProductGridMoneyField
	 */
	class ProductGridMoneyField extends ProductGridStringField
	{
		renderEnabledField()
		{
			const moneyFormat = Money.create({
				amount: 0,
				currency: this.props.currency,
			}).format;
			const groupSeparator = jnComponent.convertHtmlEntities(moneyFormat.THOUSANDS_SEP);

			return MoneyField({
				...this.getNativeFieldProps(),
				useGroupSeparator: true,
				groupSize: 3,
				groupSeparator: Boolean(groupSeparator) ? groupSeparator : ' ',
				decimalDigits: moneyFormat.DECIMALS,
				decimalSeparator: moneyFormat.DEC_POINT,
				hideZero: moneyFormat.HIDE_ZERO === 'Y',
				value: stringify(this.value),
				keyboardType: 'decimal-pad',
			});
		}

		get currentlyRenderingValue()
		{
			return stringify(this.formattedValue);
		}

		defaultFormatter()
		{
			return (raw) => {
				const amount = String(raw).replace(',', '.').trim();
				const { currency } = this.props;
				return Money.create({amount, currency}).formattedAmount;
			};
		}
	}

	module.exports = { ProductGridMoneyField };

});