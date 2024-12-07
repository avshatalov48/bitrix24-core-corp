/**
 * @module stafftrack/analytics/enum/image-bool
 */
jn.define('stafftrack/analytics/enum/image-bool', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class ImageBoolEnum
	 */
	class ImageBoolEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static IMAGE_Y = new ImageBoolEnum('IMAGE_Y', 'image_Y');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static IMAGE_N = new ImageBoolEnum('IMAGE_N', 'image_N');
	}

	module.exports = { ImageBoolEnum };
});