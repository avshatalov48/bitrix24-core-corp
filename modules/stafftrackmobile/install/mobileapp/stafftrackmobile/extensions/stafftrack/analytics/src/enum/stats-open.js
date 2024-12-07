/**
 * @module stafftrack/analytics/enum/stats-open
 */
jn.define('stafftrack/analytics/enum/stats-open', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class StatsOpenEnum
	 */
	class StatsOpenEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static PERSONAL = new StatsOpenEnum('PERSONAL', 'personal');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static DEPARTMENT = new StatsOpenEnum('DEPARTMENT', 'department');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static COLLEAGUE = new StatsOpenEnum('COLLEAGUE', 'colleague');
	}

	module.exports = { StatsOpenEnum };
});