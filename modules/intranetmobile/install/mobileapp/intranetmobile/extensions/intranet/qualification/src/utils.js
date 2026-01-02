/**
 * @module intranet/qualification/utils
 */
jn.define('intranet/qualification/utils', (require, exports, module) => {
	const { Color } = require('tokens');

	function parseColorCode(text)
	{
		const colorCodeRegex = /\[COLOR=(\w+)]/g;

		if (!colorCodeRegex.test(text))
		{
			return text;
		}

		return text.replaceAll(colorCodeRegex, (_, color) => `[COLOR=${Color[color]?.toHex() || ''}]`);
	}

	function getImageDimensions(originalWidth, originalHeight)
	{
		const width = device.screen.width;
		const height = (width / originalWidth) * originalHeight;

		return {
			width,
			height,
		};
	}

	module.exports = {
		parseColorCode,
		getImageDimensions,
	};
});
