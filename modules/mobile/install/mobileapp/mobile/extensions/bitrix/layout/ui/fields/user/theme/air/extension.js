/**
 * @module layout/ui/fields/user/theme/air
 */
jn.define('layout/ui/fields/user/theme/air', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { UserFieldClass } = require('layout/ui/fields/user');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { EmptyContent } = require('layout/ui/fields/user/theme/air/src/empty-content');
	const { EntityList } = require('layout/ui/fields/user/theme/air/src/entity-list');

	/**
	 * @param  {UserField} field - instance of the UserFieldClass.
	 * @return {function} - functional component
	 */
	const AirTheme = ({ field }) => FieldWrapper(
		{ field },
		View(
			{
				style: {
					paddingVertical: field.isMultiple() ? 0 : Indent.L.toNumber(),
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
		),
	);

	/** @type {function(object): object} */
	const UserField = withTheme(UserFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		UserField,
	};
});
