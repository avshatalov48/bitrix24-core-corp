/**
 * @module tasks/layout/fields/user-fields/theme/air/edit-button
 */
jn.define('tasks/layout/fields/user-fields/theme/air/edit-button', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text4 } = require('ui-system/typography');
	const { Loc } = require('tasks/loc');

	const EditButton = (testId) => View(
		{
			style: {
				flexDirection: 'row',
				alignItems: 'center',
				paddingTop: Indent.XS2.toNumber() + Indent.XL.toNumber(),
				paddingBottom: Indent.XS.toNumber(),
			},
			testId: `${testId}_EDIT_BUTTON`,
		},
		IconView({
			size: 24,
			icon: Icon.EDIT,
			color: Color.base4,
		}),
		Text4({
			style: {
				color: Color.base4.toHex(),
				marginLeft: Indent.M.toNumber(),
			},
			text: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_EDIT_FIELDS'),
			numberOfLines: 1,
			ellipsize: 'end',
		}),
	);

	module.exports = { EditButton };
});
