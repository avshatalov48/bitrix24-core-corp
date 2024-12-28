/**
 * @module tasks/layout/fields/user-fields/view
 */
jn.define('tasks/layout/fields/user-fields/view', (require, exports, module) => {
	const { UserFieldType } = require('tasks/enum');
	const { ViewBaseField } = require('tasks/layout/fields/user-fields/view/field/base');
	const { ViewBooleanField } = require('tasks/layout/fields/user-fields/view/field/boolean');
	const { ViewDateTimeField } = require('tasks/layout/fields/user-fields/view/field/datetime');
	const { ViewDoubleField } = require('tasks/layout/fields/user-fields/view/field/double');
	const { ViewStringField } = require('tasks/layout/fields/user-fields/view/field/string');
	const { Line } = require('utils/skeleton');
	const { Indent } = require('tokens');

	class UserFieldsView extends LayoutComponent
	{
		render()
		{
			const { isLoaded, userFields, testId, onClick, shouldShowEditButton } = this.props;

			if (!isLoaded)
			{
				return Line('90%', 8, Indent.S.toNumber());
			}

			const filledUserFields = userFields.filter((userField) => (
				userField.isMultiple
					? userField.value.some((val) => val !== '')
					: userField.value !== ''
			));

			return View(
				{
					testId: `${testId}_CONTENT`,
				},
				...filledUserFields.map((userField, index) => {
					const userFieldProps = {
						...userField,
						onClick,
						testId: `${testId}_FIELD_${userField.id}`,
						isFirst: index === 0,
						isLast: index === filledUserFields.length - 1,
					};

					switch (userField.type)
					{
						case UserFieldType.BOOLEAN:
							return new ViewBooleanField(userFieldProps);

						case UserFieldType.DATETIME:
							return new ViewDateTimeField(userFieldProps);

						case UserFieldType.DOUBLE:
							return new ViewDoubleField(userFieldProps);

						case UserFieldType.STRING:
							return new ViewStringField(userFieldProps);

						default:
							return new ViewBaseField(userFieldProps);
					}
				}),
			);
		}
	}

	module.exports = { UserFieldsView };
});
