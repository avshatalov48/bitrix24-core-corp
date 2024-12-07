/**
 * @module ui-system/typography/text-input
 */
jn.define('ui-system/typography/text-input', (require, exports, module) => {
	const { TextBase } = require('ui-system/typography/text-base');

	module.exports = {
		TextInput: (props) => TextBase({ nativeElement: TextInput, ...props }),
	};
});
