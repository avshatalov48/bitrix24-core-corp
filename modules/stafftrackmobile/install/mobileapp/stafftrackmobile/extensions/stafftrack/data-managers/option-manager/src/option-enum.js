/**
 * @module stafftrack/data-managers/option-manager/option-enum
 */
jn.define('stafftrack/data-managers/option-manager/option-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	class OptionEnum extends BaseEnum
	{
		static DEFAULT_MESSAGE = new OptionEnum('DEFAULT_MESSAGE', 'defaultMessage');
		static DEFAULT_LOCATION = new OptionEnum('DEFAULT_LOCATION', 'defaultLocation');
		static DEFAULT_CUSTOM_LOCATION = new OptionEnum('DEFAULT_CUSTOM_LOCATION', 'defaultCustomLocation');
		static SEND_MESSAGE = new OptionEnum('SEND_MESSAGE', 'sendMessage');
		static SEND_GEO = new OptionEnum('SEND_GEO', 'sendGeo');
		static SELECTED_DEPARTMENT_ID = new OptionEnum('SELECTED_DEPARTMENT_ID', 'selectedDepartmentId');
		static IS_FIRST_HELP_VIEWED = new OptionEnum('IS_FIRST_HELP_VIEWED', 'isFirstHelpViewed');
		static TIMEMAN_INTEGRATION_ENABLED = new OptionEnum('TIMEMAN_INTEGRATION_ENABLED', 'timemanIntegrationEnabled');
	}

	module.exports = { OptionEnum };
});
