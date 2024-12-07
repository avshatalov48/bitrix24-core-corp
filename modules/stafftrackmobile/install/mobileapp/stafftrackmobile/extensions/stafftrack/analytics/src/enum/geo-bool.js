/**
 * @module stafftrack/analytics/enum/geo-bool
 */
jn.define('stafftrack/analytics/enum/geo-bool', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class GeoBoolEnum
	 */
	class GeoBoolEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static GEO_Y = new GeoBoolEnum('GEO_Y', 'geo_Y');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static GEO_N = new GeoBoolEnum('GEO_N', 'geo_N');
	}

	module.exports = { GeoBoolEnum };
});