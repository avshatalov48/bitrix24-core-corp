/**
 * @module stafftrack/model/shift/pull-command
 */
jn.define('stafftrack/model/shift/pull-command', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class PullCommandEnum
	 */
	class PullCommandEnum extends BaseEnum
	{
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static SHIFT_ADD = new PullCommandEnum('SHIFT_ADD', 'shift_add');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static SHIFT_UPDATE = new PullCommandEnum('SHIFT_UPDATE', 'shift_update');
		// eslint-disable-next-line @bitrix24/bitrix24-janative/no-static-variable-in-class
		static SHIFT_DELETE = new PullCommandEnum('SHIFT_DELETE', 'shift_delete');
	}

	module.exports = { PullCommandEnum };
});