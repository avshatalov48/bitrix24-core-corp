/**
 * @module ui-system/typography/heading
 */
jn.define('ui-system/typography/heading', (require, exports, module) => {
	const { Text } = require('ui-system/typography/text');

	const HeadingText = (props) => Text({ header: true, ...props });

	module.exports = {
		H1: (props) => HeadingText({ ...props, size: 1, header: true }),
		H2: (props) => HeadingText({ ...props, size: 2, header: true }),
		H3: (props) => HeadingText({ ...props, size: 3, header: true }),
		H4: (props) => HeadingText({ ...props, size: 4, header: true }),
		H5: (props) => HeadingText({ ...props, size: 5, header: true }),
	};
});
