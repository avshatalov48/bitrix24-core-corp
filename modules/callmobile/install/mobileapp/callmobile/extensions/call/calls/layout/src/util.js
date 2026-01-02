jn.define('src/util', (require, exports, module) => {
	const { Color } = require('tokens');
	function convertHexToColorEnum(color)
	{
		return new Color('avatarColor', color);
	}

	function isVisible(num, min, max)
	{
		return num >= min && num <= max;
	}

	module.exports = {
		convertHexToColorEnum,
		isVisible,
	};
});
