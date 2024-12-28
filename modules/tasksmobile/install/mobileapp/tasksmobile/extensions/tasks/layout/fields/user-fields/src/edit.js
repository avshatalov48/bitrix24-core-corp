/**
 * @module tasks/layout/fields/user-fields/edit
 */
jn.define('tasks/layout/fields/user-fields/edit', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonSize } = require('ui-system/form/buttons');
	const { Color } = require('tokens');
	const { Loc } = require('tasks/loc');
	const { Indent } = require('tokens');
	const { showFieldsValidationError } = require('tasks/layout/fields/user-fields/validator');
	const { UserFieldType } = require('tasks/enum');
	const { EditBaseField } = require('tasks/layout/fields/user-fields/edit/field/base');
	const { EditBooleanField } = require('tasks/layout/fields/user-fields/edit/field/boolean');
	const { EditDateTimeField } = require('tasks/layout/fields/user-fields/edit/field/datetime');
	const { EditDoubleField } = require('tasks/layout/fields/user-fields/edit/field/double');
	const { EditStringField } = require('tasks/layout/fields/user-fields/edit/field/string');

	const { connect } = require('statemanager/redux/connect');
	const { selectByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks');

	class UserFieldsEdit extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {StringInput|null} */
			this.focusedInputRef = null;
			this.changedFields = new Map();
			this.fieldRefs = new Set();

			this.state = {
				shouldShowErrors: Boolean(props.shouldShowErrors),
			};

			this.onRef = this.onRef.bind(this);
			this.onFocus = this.onFocus.bind(this);
			this.onChange = this.onChange.bind(this);
		}

		componentDidMount()
		{
			if (this.state.shouldShowErrors)
			{
				showFieldsValidationError(this.props.layout);
			}
		}

		render()
		{
			const { testId, layout, onChange } = this.props;

			return Box(
				{
					style: {
						paddingBottom: Indent.XL.toNumber(),
					},
					withScroll: true,
					scrollProps: {
						ref: (ref) => {
							this.boxScrollRef = ref;
						},
					},
					backgroundColor: Color.bgContentPrimary,
					footer: BoxFooter(
						{
							safeArea: true,
							keyboardButton: {
								text: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_EDIT_OK'),
								testId: `${testId}_KEYBOARD_HIDE_BUTTON`,
								onClick: () => this.focusedInputRef?.blur?.(),
							},
						},
						Button({
							text: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_EDIT_SAVE'),
							size: ButtonSize.L,
							stretched: true,
							testId: `${testId}_SAVE_BUTTON`,
							onClick: () => {
								if (this.canSave())
								{
									onChange?.(this.changedFields);
									layout.close();
								}
								else
								{
									showFieldsValidationError(layout);
									this.setState({ shouldShowErrors: true });
								}
							},
						}),
					),
				},
				...this.userFields.map((userField) => {
					const userFieldProps = {
						...userField,
						layout,
						shouldShowErrors: this.state.shouldShowErrors,
						testId: `${testId}_FIELD_${userField.id}`,
						ref: this.onRef,
						onFocus: this.onFocus,
						onChange: this.onChange,
					};

					if (this.changedFields.has(userField.fieldName))
					{
						userFieldProps.value = this.changedFields.get(userField.fieldName);
					}

					switch (userField.type)
					{
						case UserFieldType.BOOLEAN:
							return new EditBooleanField(userFieldProps);

						case UserFieldType.DATETIME:
							return new EditDateTimeField(userFieldProps);

						case UserFieldType.DOUBLE:
							return new EditDoubleField(userFieldProps);

						case UserFieldType.STRING:
							return new EditStringField(userFieldProps);

						default:
							return new EditBaseField(userFieldProps);
					}
				}),
			);
		}

		canSave()
		{
			return [...this.fieldRefs].every((field) => field.isValid());
		}

		onFocus(ref)
		{
			this.focusedInputRef = ref;

			setTimeout(
				() => {
					const position = this.boxScrollRef?.getPosition(ref.getRef());
					if (position)
					{
						this.boxScrollRef?.scrollTo({
							y: position.y - 180,
							animated: true,
						});
					}
				},
				Application.getPlatform() === 'ios' ? 0 : 300,
			);
		}

		onChange(fieldName, value)
		{
			this.changedFields.set(fieldName, value);
		}

		onRef(ref)
		{
			this.fieldRefs.add(ref);
		}

		get userFields()
		{
			return this.props.userFields;
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const taskId = ownProps.taskId;
		const userFields = (selectByTaskIdOrGuid(state, taskId)?.userFields || ownProps.userFields || []);

		return { userFields };
	};

	module.exports = {
		UserFieldsEdit: connect(mapStateToProps)(UserFieldsEdit),
	};
});
