/**
 * @module stafftrack/model/shift/location
 */
jn.define('stafftrack/model/shift/location', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class LocationEnum
	 */
	class LocationEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static REMOTELY = new LocationEnum('REMOTELY', 'STAFFTRACK_LOCATION_REMOTELY');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static HOME = new LocationEnum('HOME', 'STAFFTRACK_LOCATION_HOME');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static OFFICE = new LocationEnum('OFFICE', 'STAFFTRACK_LOCATION_OFFICE');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static OUTSIDE = new LocationEnum('OUTSIDE', 'STAFFTRACK_LOCATION_OUTSIDE');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static CUSTOM = new LocationEnum('CUSTOM', 'STAFFTRACK_LOCATION_CUSTOM');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static DELETED = new LocationEnum('DELETED', 'STAFFTRACK_LOCATION_DELETED');
	}

	module.exports = { LocationEnum };
});
