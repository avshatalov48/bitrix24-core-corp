/**
 * @module tasks/layout/fields/user-fields/view/field/boolean
 */
jn.define('tasks/layout/fields/user-fields/view/field/boolean', (require, exports, module) => {
	const { ViewBaseField } = require('tasks/layout/fields/user-fields/view/field/base');
	const { Icon } = require('assets/icons');

	class ViewBooleanField extends ViewBaseField
	{
		prepareValue(value)
		{
			const { yesLabel, noLabel } = this.settings;

			return (value === '1' ? yesLabel : noLabel);
		}

		get icon()
		{
			return Icon.SWITCHER;
		}
	}

	module.exports = { ViewBooleanField };
});
