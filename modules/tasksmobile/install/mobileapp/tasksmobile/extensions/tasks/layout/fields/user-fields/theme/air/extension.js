/**
 * @module tasks/layout/fields/user-fields/theme/air
 */
jn.define('tasks/layout/fields/user-fields/theme/air', (require, exports, module) => {
	const { UserFieldsFieldClass } = require('tasks/layout/fields/user-fields');
	const { withTheme } = require('layout/ui/fields/theme');
	const { Title } = require('layout/ui/fields/theme/air/elements/title');
	const { UserFieldsView } = require('tasks/layout/fields/user-fields/view');
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Loc } = require('tasks/loc');
	const { EditButton } = require('tasks/layout/fields/user-fields/theme/air/edit-button');

	/** @param  {UserFieldsField} field */
	const AirTheme = (field) => {
		const shouldShowEditButton = !field.isRestricted() && !field.isReadOnly();

		return View(
			{
				style: {
					flexDirection: 'column',
				},
				ref: field.bindContainerRef,
				onClick: field.onContentClick,
				testId: `${field.testId}_FIELD`,
			},
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginBottom: Indent.XL.toNumber(),
					},
				},
				field.isRestricted() && IconView({
					style: {
						marginHorizontal: Indent.XS2.toNumber(),
					},
					icon: Icon.LOCK,
					color: Color.base1,
					size: 20,
					testId: `${field.testId}_LOCK`,
				}),
				Title({
					text: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_AIR_TITLE'),
					textMultiple: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_AIR_TITLE_MULTI'),
					testId: field.testId,
					count: field.getFilledUserFieldsCount(),
				}),
			),
			new UserFieldsView({
				isLoaded: field.isLoaded(),
				userFields: field.userFields,
				testId: field.testId,
				onClick: field.onContentClick,
			}),
			shouldShowEditButton && EditButton(field.testId),
		);
	};

	/** @type {function(object): object} */
	const UserFieldsField = withTheme(UserFieldsFieldClass, AirTheme);

	module.exports = { UserFieldsField };
});
