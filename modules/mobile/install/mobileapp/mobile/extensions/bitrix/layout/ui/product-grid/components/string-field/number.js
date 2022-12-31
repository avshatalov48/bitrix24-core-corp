/**
 * @module layout/ui/product-grid/components/string-field/number
 */
jn.define('layout/ui/product-grid/components/string-field/number', (require, exports, module) => {

	const { ProductGridStringField } = require('layout/ui/product-grid/components/string-field/string');
	const { stringify } = require('utils/string');

	/**
	 * @class ProductGridNumberField
	 */
	class ProductGridNumberField extends ProductGridStringField
	{
		renderEnabledField()
		{
			const moneyFieldProps = {
				useGroupSeparator: BX.prop.getBoolean(this.props, 'useGroupSeparator', true),
				hideZero: BX.prop.getBoolean(this.props, 'hideZero', true),
				keyboardType: BX.prop.getString(this.props, 'keyboardType', 'decimal-pad'),
			};

			['groupSize', 'groupSeparator', 'decimalDigits', 'decimalSeparator'].map(code => {
				if (this.props.hasOwnProperty(code))
				{
					moneyFieldProps[code] = this.props[code];
				}
			});

			return MoneyField({
				...this.getNativeFieldProps(),
				...moneyFieldProps,
				value: stringify(this.value),
			});
		}

		get currentlyRenderingValue()
		{
			return stringify(this.formattedValue);
		}
	}

	module.exports = { ProductGridNumberField };

});