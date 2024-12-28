/**
 * @module tasks/layout/fields/user-fields/theme/air-compact
 */
jn.define('tasks/layout/fields/user-fields/theme/air-compact', (require, exports, module) => {
	const { Loc } = require('tasks/loc');
	const { Icon } = require('assets/icons');
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact');
	const { withTheme } = require('layout/ui/fields/theme');
	const { UserFieldsFieldClass } = require('tasks/layout/fields/user-fields');

	/**
	 * @param {UserFieldsField} field
	 */
	const AirTheme = (field) => {
		return AirCompactThemeView({
			testId: field.testId,
			bindContainerRef: field.bindContainerRef,
			empty: field.isEmpty(),
			readOnly: field.isReadOnly(),
			isRestricted: field.isRestricted(),
			count: field.getFilledUserFieldsCount(),
			leftIcon: {
				icon: field.isRestricted() ? Icon.LOCK : Icon.MY_PLAN,
			},
			text: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_AIR_COMPACT_TITLE'),
			textMultiple: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_AIR_COMPACT_TITLE_MULTI'),
			onClick: field.onContentClick,
			multiple: true,
			wideMode: true,
		});
	};

	/** @type {function(object): object} */
	const UserFieldsField = withTheme(UserFieldsFieldClass, AirTheme);

	module.exports = { UserFieldsField };
});
