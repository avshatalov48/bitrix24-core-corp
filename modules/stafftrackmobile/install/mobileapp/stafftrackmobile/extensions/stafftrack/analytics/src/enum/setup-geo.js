/**
 * @module stafftrack/analytics/enum/setup-geo
 */
jn.define('stafftrack/analytics/enum/setup-geo', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class SetupGeoEnum
	 */
	class SetupGeoEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static TURN_ON = new SetupGeoEnum('TURN_ON', 'turn_on');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static TURN_OFF = new SetupGeoEnum('TURN_OFF', 'turn_off');
	}

	module.exports = { SetupGeoEnum };
});