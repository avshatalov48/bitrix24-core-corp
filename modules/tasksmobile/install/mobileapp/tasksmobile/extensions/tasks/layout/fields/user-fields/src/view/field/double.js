/**
 * @module tasks/layout/fields/user-fields/view/field/double
 */
jn.define('tasks/layout/fields/user-fields/view/field/double', (require, exports, module) => {
	const { ViewBaseField } = require('tasks/layout/fields/user-fields/view/field/base');
	const { Icon } = require('assets/icons');

	class ViewDoubleField extends ViewBaseField
	{
		get icon()
		{
			return Icon.QUANTITY;
		}
	}

	module.exports = { ViewDoubleField };
});
