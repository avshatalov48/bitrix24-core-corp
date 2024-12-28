/**
 * @module tasks/layout/fields/user-fields/view/field/string
 */
jn.define('tasks/layout/fields/user-fields/view/field/string', (require, exports, module) => {
	const { ViewBaseField } = require('tasks/layout/fields/user-fields/view/field/base');
	const { Icon } = require('assets/icons');

	class ViewStringField extends ViewBaseField
	{
		get icon()
		{
			return Icon.TEXT;
		}
	}

	module.exports = { ViewStringField };
});
