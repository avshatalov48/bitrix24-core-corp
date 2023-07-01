/**
 * @module crm/required-fields
 */

jn.define('crm/required-fields', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { RequiredFieldsBackdrop } = require('crm/required-fields/required-backdrop');

	const REQUIRED_ERROR_CODE = 'CRM_FIELD_ERROR_REQUIRED';

	/**
	 * @class RequiredFields
	 */
	class RequiredFields
	{
		static show(props)
		{
			const self = new RequiredFields(props);
			self.showRequiredFields();
		}

		static getRequiredFields(errors)
		{
			return errors
				.filter(({ code }) => code === REQUIRED_ERROR_CODE)
				.map(({ customData }) => customData.fieldName);
		}

		static hasRequiredFields(errors)
		{
			const requiredFields = this.getRequiredFields(errors);
			return requiredFields.length > 0;
		}

		constructor(props)
		{
			this.props = props;
			this.onCancelEvent = this.handleOnCancelEvent.bind(this);
			this.onSaveFieldsValue = this.saveFieldsValue.bind(this);
		}

		showRequiredFields()
		{
			const { errors, params } = this.props;
			const fieldCodes = RequiredFields.getRequiredFields(errors);

			return (
				BX.ajax.runAction('crmmobile.EntityDetails.getRequiredFields', {
					json: { ...params, fieldCodes },
				})
					.then(({ data }) => this.showComponent(data, fieldCodes.length))
					.catch((errors) => this.showErrors(errors))
			);
		}

		getMediumPositionPercent(fieldLength)
		{
			return Math.min((fieldLength + 2) * 10, 85);
		}

		showComponent(editorData, fieldLength)
		{
			Haptics.notifyWarning();

			PageManager.openWidget('layout', {
				modal: true,
				backgroundColor: '#eef2f4',
				backdrop: {
					swipeAllowed: false,
					horizontalSwipeAllowed: false,
					shouldResizeContent: true,
					swipeContentAllowed: false,
					navigationBarColor: '#eef2f4',
				},
				onReady: (layout) => {
					layout.showComponent(new RequiredFieldsBackdrop({
						layout,
						editorData,
						onClose: this.onCancelEvent,
						onSave: this.onSaveFieldsValue,
					}));
				},
			});
		}

		saveFieldsValue(value)
		{
			const { params, action } = this.props;

			if (!params.entityId || action === 'save')
			{
				this.handleOnSave(value);

				return;
			}

			BX.ajax.runAction('crmmobile.EntityDetails.update', {
				json: {
					...params, data: value,
				},
			})
				.then(() => this.handleOnSave(value))
				.catch((errors) => this.showErrors(errors))
			;
		}

		handleOnSave(value)
		{
			const { onSave } = this.props;
			if (typeof onSave === 'function')
			{
				onSave({ value });
			}
		}

		showErrors({ errors })
		{
			this.handleOnCancelEvent();

			errors = Array.isArray(errors) ? errors : [];
			errors = errors.filter(({ customData, message }) => customData && customData.public && message);

			let title = Loc.getMessage('MCRM_REQUIRED_FIELDS_ERROR_ON_SAVE');
			if (errors.length === 0)
			{
				title = Loc.getMessage('MCRM_REQUIRED_FIELDS_ERROR_ON_SAVE_INTERNAL');
			}

			ErrorNotifier.showErrors(errors, {
				title,
				addDefaultIfEmpty: true,
				defaultErrorText: Loc.getMessage('MCRM_REQUIRED_FIELDS_ERROR_ON_SAVE_INTERNAL_TEXT'),
			});
		}

		handleOnCancelEvent()
		{
			const { onCancel } = this.props;
			if (onCancel)
			{
				onCancel();
			}
		}
	}

	module.exports = { RequiredFields };
});
