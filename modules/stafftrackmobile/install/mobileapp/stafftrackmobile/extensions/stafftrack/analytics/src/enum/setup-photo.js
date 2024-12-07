/**
 * @module stafftrack/analytics/enum/setup-photo
 */
jn.define('stafftrack/analytics/enum/setup-photo', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class SetupPhotoEnum
	 */
	class SetupPhotoEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static NEW = new SetupPhotoEnum('NEW', 'new');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static FROM_GALLERY = new SetupPhotoEnum('FROM_GALLERY', 'from_gallery');
	}

	module.exports = { SetupPhotoEnum };
});