/**
 * @module ui-system/typography/text-field
 */
jn.define('ui-system/typography/text-field', (require, exports, module) => {
	const { TextBase } = require('ui-system/typography/text-base');

	module.exports = {
		TextField: (props) => TextBase({ nativeElement: TextField, ...props }),
	};
});
