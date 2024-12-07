/**
 * @module stafftrack/ajax/option
 */
jn.define('stafftrack/ajax/option', (require, exports, module) => {
	const { BaseAjax } = require('stafftrack/ajax/base');

	const OptionActions = {
		SAVE_SELECTED_DEPARTMENT_ID: 'saveSelectedDepartmentId',
		HANDLE_FIRST_HELP_VIEW: 'handleFirstHelpView',
		CHANGE_TIMEMAN_INTEGRATION_OPTION: 'changeTimemanIntegrationOption',
	};

	class OptionAjax extends BaseAjax
	{
		/**
		 * @returns {string}
		 */
		getEndpoint()
		{
			return 'stafftrack.Option';
		}

		/**
		 * @param departmentId {number}
		 * @returns {Promise<Object, void>}
		 */
		saveSelectedDepartmentId(departmentId)
		{
			return this.fetch(OptionActions.SAVE_SELECTED_DEPARTMENT_ID, { departmentId });
		}

		/**
		 *
		 * @returns {Promise<Object, void>}
		 */
		handleFirstHelpView()
		{
			return this.fetch(OptionActions.HANDLE_FIRST_HELP_VIEW);
		}

		/**
		 *
		 * @param enabled {string}
		 * @returns {Promise<Object, void>}
		 */
		changeTimemanIntegrationOption(enabled)
		{
			return this.fetch(OptionActions.CHANGE_TIMEMAN_INTEGRATION_OPTION, { enabled });
		}
	}

	module.exports = { OptionAjax: new OptionAjax() };
});
