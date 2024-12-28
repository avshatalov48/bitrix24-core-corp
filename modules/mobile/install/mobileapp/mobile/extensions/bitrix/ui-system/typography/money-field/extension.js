/**
 * @module ui-system/typography/money-field
 */
jn.define('ui-system/typography/money-field', (require, exports, module) => {
	const { TextBase } = require('ui-system/typography/text-base');

	module.exports = {
		/**
		 * @param {MoneyFieldProps} props
		 */
		MoneyField: (props) => TextBase({ nativeElement: MoneyField, ...props }),
	};
});
