/**
 * @module stafftrack/model/counter/mute
 */
jn.define('stafftrack/model/counter/mute', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class MuteEnum
	 */
	class MuteEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static DISABLED = new MuteEnum('DISABLED', 0);
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static PERMANENT = new MuteEnum('PERMANENT', 1);
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static TEMPORALLY = new MuteEnum('TEMPORALLY', 2);
	}

	module.exports = { MuteEnum };
});
