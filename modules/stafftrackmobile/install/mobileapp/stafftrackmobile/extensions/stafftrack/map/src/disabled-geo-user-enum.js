/**
 * @module stafftrack/map/disabled-geo-user-enum
 */
jn.define('stafftrack/map/disabled-geo-user-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	class DisabledGeoUserEnum extends BaseEnum
	{
		static REGULAR = new DisabledGeoUserEnum('REGULAR', 'regular');
		static ADMIN = new DisabledGeoUserEnum('ADMIN', 'admin');
	}

	module.exports = { DisabledGeoUserEnum };
});
