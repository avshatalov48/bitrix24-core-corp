/**
 * @module ui-system/typography/phone-field
 */
jn.define('ui-system/typography/phone-field', (require, exports, module) => {
	const { TextBase } = require('ui-system/typography/text-base');

	module.exports = {
		PhoneNumberField: (props) => TextBase({ nativeElement: PhoneNumberField, ...props }),
	};
});
