/**
 * @module ui-system/typography
 */
jn.define('ui-system/typography', (require, exports, module) => {
	const { BBCodeText } = require('ui-system/typography/bbcodetext');
	const { H1, H2, H3, H4, H5 } = require('ui-system/typography/heading');
	const { Text, Text1, Text2, Text3, Text4, Text5, Text6, Text7, Capital } = require('ui-system/typography/text');

	module.exports = { H1, H2, H3, H4, H5, Text, Text1, Text2, Text3, Text4, Text5, Text6, Text7, Capital, BBCodeText };
});
