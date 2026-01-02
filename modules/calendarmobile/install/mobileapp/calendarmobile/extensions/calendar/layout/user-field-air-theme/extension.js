/**
 * @module calendar/layout/user-field-air-theme
 */
jn.define('calendar/layout/user-field-air-theme', (require, exports, module) => {
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { EmptyContent } = require('layout/ui/fields/user/theme/air/src/empty-content');
	const { EntityList } = require('layout/ui/fields/user/theme/air/src/entity-list');

	const UserFieldAirTheme = ({ field }) => FieldWrapper(
		{ field },
		View(
			{
				style: {
					flexDirection: 'column',
				},
			},
			!field.isEmpty() && field.hasCustomTitle() && field.renderCustomTitle(),
			View(
				{
					style: {
						paddingVertical: 0,
						flexDirection: 'row',
						alignItems: 'center',
						...field.getStyles().airContainer,
					},
				},
				field.isEmpty()
					? View(
						{
							testId: `${field.testId}_CONTENT`,
						},
						EmptyContent({
							testId: `${field.testId}_EMPTY_VIEW`,
							icon: field.getDefaultLeftIcon(),
							text: field.getEmptyText(),
						}),
					)
					: EntityList({ field }),
				!field.isEmpty() && field.hasPermissions() && field.renderRightIcons(),
			),
		),

	);

	module.exports = { UserFieldAirTheme };
});
