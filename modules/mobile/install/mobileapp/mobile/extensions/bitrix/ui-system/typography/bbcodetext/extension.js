/**
 * @module ui-system/typography/bbcodetext
 */
jn.define('ui-system/typography/bbcodetext', (require, exports, module) => {
	const { TextBase } = require('ui-system/typography/text-base');

	module.exports = {
		BBCodeText: (props) => TextBase({ nativeElement: BBCodeText, ...props }),
	};
});
