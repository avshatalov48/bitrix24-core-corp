/**
 * @module stafftrack/data-managers/option-manager
 */
jn.define('stafftrack/data-managers/option-manager', (require, exports, module) => {
	const { OptionAjax } = require('stafftrack/ajax');
	const { OptionEnum } = require('stafftrack/data-managers/option-manager/option-enum');

	class OptionManager
	{
		/**
		 *
		 * @returns {object}
		 */
		getOptions()
		{
			return this.options;
		}

		/**
		 *
		 * @param option {OptionEnum}
		 * @returns {*|null}
		 */
		getOption(option)
		{
			return this.options[option.getValue()] ?? null;
		}

		/**
		 *
		 * @param options
		 */
		setOptions(options)
		{
			this.options = options;
		}

		/**
		 *
		 * @param option {OptionEnum}
		 * @param value
		 */
		setOption(option, value)
		{
			this.options[option.getValue()] = value;
		}

		saveSelectedDepartmentId(selectedDepartmentId)
		{
			void OptionAjax.saveSelectedDepartmentId(selectedDepartmentId);
			this.setOption(OptionEnum.SELECTED_DEPARTMENT_ID, selectedDepartmentId);
		}

		saveTimemanIntegrationEnabled(timemanIntegrationEnabled)
		{
			void OptionAjax.changeTimemanIntegrationOption(timemanIntegrationEnabled ? 'Y' : 'N');
			this.setOption(OptionEnum.TIMEMAN_INTEGRATION_ENABLED, timemanIntegrationEnabled);
		}

		handleFirstHelpView()
		{
			void OptionAjax.handleFirstHelpView();
			this.setOption(OptionEnum.IS_FIRST_HELP_VIEWED, true);
		}
	}

	module.exports = {
		OptionManager: new OptionManager(),
		OptionEnum,
	};
});
