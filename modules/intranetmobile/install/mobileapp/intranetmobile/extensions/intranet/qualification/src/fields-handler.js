/**
 * @module intranet/qualification/fields-handler
 */
jn.define('intranet/qualification/fields-handler', (require, exports, module) => {
	const { Type } = require('type');
	const { Indent } = require('tokens');
	const { useCallback } = require('utils/function');
	const { FieldType } = require('intranet/qualification/const');
	const { FieldsTriggersHandler } = require('intranet/qualification/fields-triggers-handler');
	const { Phone } = require('intranet/qualification/fields/phone');
	const { BalloonSelect } = require('intranet/qualification/fields/balloon-select');
	const { UserInvitation } = require('intranet/qualification/fields/user-invitation');

	class FieldsHandler
	{
		/**
		 * @param {Object} props
		 * @param {string} props.stepId
		 * @param {Wizard} props.wizard
		 * @param {Array} props.fields
		 * @param {PageManager} [props.layoutWidget=PageManager]
		 * @param {Function} [props.onFieldsValuesUpdate]
		 */
		constructor(props)
		{
			this.fields = props.fields.filter((field) => Object.values(FieldType).includes(field.type));
			this.layoutWidget = props.layoutWidget || PageManager;
			this.onFieldsValuesUpdate = props.onFieldsValuesUpdate;

			this.fieldsValues = new Map();
			this.fieldsValidity = new Map();

			this.resetFieldsValues();

			this.triggersHandler = new FieldsTriggersHandler({
				stepId: props.stepId,
				wizard: props.wizard,
				fields: this.fields,
				fieldsValues: this.fieldsValues,
				layoutWidget: this.layoutWidget,
			});
		}

		resetFieldsValues()
		{
			this.fields.forEach(({ id, type }) => {
				this.#updateFieldValue(id, null);
				this.#updateFieldValidity(id, type);
			});
			this.onFieldsValuesUpdate?.();
		}

		#updateFieldValue(id, value)
		{
			this.fieldsValues.set(id, value);
		}

		#updateFieldValidity(id, type)
		{
			const value = this.fieldsValues.get(id);

			switch (type)
			{
				case FieldType.BALLOON_ONE_SELECT:
					this.fieldsValidity.set(id, Type.isArrayFilled(Object.keys(value || {})));
					break;

				case FieldType.PHONE_INPUT:
					this.fieldsValidity.set(id, Phone.isValidPhone(value?.value));
					break;

				case FieldType.USER_INVITATION:
					this.fieldsValidity.set(id, Boolean(value));
					break;

				default:
					this.fieldsValidity.set(id, false);
			}
		}

		handleFieldsTriggers(stepSwitchButtonType)
		{
			return this.triggersHandler.handleFieldsTriggers(stepSwitchButtonType);
		}

		areAllFieldsValid()
		{
			return [...this.fieldsValidity.values()].every((isValid) => isValid);
		}

		getAnalyticsData()
		{
			if (!Type.isArrayFilled(this.fields))
			{
				return JSON.stringify([]);
			}

			const analyticsData = this.fields.map(({ id, type, data }) => {
				const fieldValue = this.fieldsValues.get(id);

				switch (type)
				{
					case FieldType.BALLOON_ONE_SELECT:
					{
						return {
							id,
							type,
							value: Object.entries(fieldValue).map(([valueId, value]) => ({
								id: valueId,
								title: data.find((item) => item.id === valueId)?.title,
								value,
							})),
						};
					}

					case FieldType.PHONE_INPUT:
					case FieldType.USER_INVITATION:
					default:
						return { id, type };
				}
			});

			return JSON.stringify(analyticsData);
		}

		renderFields()
		{
			if (!Type.isArrayFilled(this.fields))
			{
				return null;
			}

			return View(
				{},
				...this.fields.map((field) => {
					return View(
						{
							style: {
								marginTop: Indent.XL3.toNumber(),
							},
						},
						this.#renderField(field),
					);
				}),
			);
		}

		#renderField({ id, type, data })
		{
			const testId = `${id}-field`;
			const onChange = useCallback((value) => {
				this.#updateFieldValue(id, value);
				this.#updateFieldValidity(id, type);
				this.onFieldsValuesUpdate?.();
			});

			switch (type)
			{
				case FieldType.BALLOON_ONE_SELECT:
					return new BalloonSelect({
						testId,
						onChange,
						items: data,
						selectedItemIds: Object.keys(this.fieldsValues.get(id) || {}),
					});

				case FieldType.PHONE_INPUT:
					return new Phone({
						testId,
						onChange,
						value: this.fieldsValues.get(id)?.value ?? '',
						agreementUrl: data.agreementUrl || '',
					});

				case FieldType.USER_INVITATION:
					return new UserInvitation({
						testId,
						onChange,
						parentWidget: this.layoutWidget,
					});

				default:
					return null;
			}
		}
	}

	module.exports = { FieldsHandler, FieldType };
});
