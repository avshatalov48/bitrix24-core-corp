/**
 * @module intranet/qualification/fields-triggers-handler
 */
jn.define('intranet/qualification/fields-triggers-handler', (require, exports, module) => {
	const { showToast, Position } = require('toast');
	const { Type } = require('type');
	const { StepSwitchButtonType } = require('layout/ui/wizard/const');
	const { FieldType, TriggerType } = require('intranet/qualification/const');
	const { setPreset, saveFieldValue } = require('intranet/qualification/api');
	const { AnalyticsHandler } = require('intranet/qualification/analytics-handler');

	class FieldsTriggersHandler
	{
		/**
		 * @param {Object} props
		 * @param {string} props.stepId
		 * @param {Wizard} props.wizard
		 * @param {Array} props.fields
		 * @param {Map} props.fieldsValues
		 * @param {PageManager} [props.layoutWidget=PageManager]
		 */
		constructor(props)
		{
			this.stepId = props.stepId;
			this.wizard = props.wizard;
			this.fields = props.fields;
			this.fieldsValues = props.fieldsValues;
			this.layoutWidget = props.layoutWidget || PageManager;
		}

		/**
		 * @param {StepSwitchButtonType} stepSwitchButtonType
		 * @return {Promise}
		 */
		handleFieldsTriggers(stepSwitchButtonType)
		{
			return Promise.allSettled(
				this.fields.map(({ id, type, triggers: rawTriggers = [] }) => {
					const triggers = rawTriggers.filter((trigger) => Object.values(TriggerType).includes(trigger.type));

					if (type === FieldType.PHONE_INPUT)
					{
						triggers.push({ type: TriggerType.SAVE_VALUE });
					}

					if (!Type.isArrayFilled(triggers))
					{
						return Promise.resolve();
					}

					return Promise.allSettled(
						triggers.map((trigger) => {
							switch (trigger.type)
							{
								case TriggerType.STEP_ACTIVATE:
									return this.#handleStepActivateTrigger(trigger, stepSwitchButtonType, id, type);

								case TriggerType.STEP_DEACTIVATE:
									return this.#handleStepDeactivateTrigger(trigger, stepSwitchButtonType, id, type);

								case TriggerType.TOAST_SHOW:
									return this.#handleToastShowTrigger(trigger, stepSwitchButtonType);

								case TriggerType.PRESET_SET:
									return this.#handlePresetSetTrigger(trigger, stepSwitchButtonType, id, type);

								case TriggerType.SAVE_VALUE:
									return this.#handleSaveValueTrigger(trigger, stepSwitchButtonType, id, type);

								default:
									return Promise.resolve();
							}
						}),
					);
				}),
			);
		}

		#handleStepActivateTrigger(trigger, stepSwitchButtonType, fieldId, fieldType)
		{
			if (stepSwitchButtonType !== StepSwitchButtonType.CONTINUE)
			{
				return Promise.resolve();
			}

			if (fieldType === FieldType.BALLOON_ONE_SELECT)
			{
				const { stepIds, values } = trigger;

				const fieldValue = Object.values(this.fieldsValues.get(fieldId) || {})[0];
				const shouldActivateSteps = values.includes(fieldValue);

				stepIds.forEach((stepId) => {
					if (shouldActivateSteps)
					{
						this.wizard.activateStep(stepId);
					}
					else
					{
						this.wizard.deactivateStep(stepId);
					}
				});
			}

			return Promise.resolve();
		}

		#handleStepDeactivateTrigger(trigger, stepSwitchButtonType, fieldId, fieldType)
		{
			if (stepSwitchButtonType !== StepSwitchButtonType.CONTINUE)
			{
				return Promise.resolve();
			}

			if (fieldType === FieldType.BALLOON_ONE_SELECT)
			{
				const { stepIds, values } = trigger;

				const fieldValue = Object.values(this.fieldsValues.get(fieldId) || {})[0];
				const shouldDeactivateSteps = values.includes(fieldValue);

				stepIds.forEach((stepId) => {
					if (shouldDeactivateSteps)
					{
						this.wizard.deactivateStep(stepId);
					}
					else
					{
						this.wizard.activateStep(stepId);
					}
				});
			}

			return Promise.resolve();
		}

		#handleToastShowTrigger(trigger, stepSwitchButtonType)
		{
			if (stepSwitchButtonType === StepSwitchButtonType.CONTINUE)
			{
				showToast(
					{
						message: trigger.message.replaceAll('\\n', '\n'),
						position: Position.TOP,
					},
					this.layoutWidget,
				);
			}

			return Promise.resolve();
		}

		#handlePresetSetTrigger(trigger, stepSwitchButtonType, fieldId, fieldType)
		{
			if (stepSwitchButtonType !== StepSwitchButtonType.CONTINUE)
			{
				return Promise.resolve();
			}

			if (fieldType === FieldType.BALLOON_ONE_SELECT)
			{
				const { values } = trigger;

				const fieldValue = Object.values(this.fieldsValues.get(fieldId) || {})[0];
				const presetId = values[fieldValue];

				if (!presetId)
				{
					return Promise.resolve();
				}

				return new Promise((resolve, reject) => {
					setPreset(presetId)
						.then((result) => {
							if (result.status === 'success')
							{
								AnalyticsHandler.handleSetPresetEvent(this.stepId, presetId);
							}

							const lastStep = this.wizard.getStepById(
								this.wizard.getStepIdByIndex(this.wizard.getTotalStepsCount() - 1),
							);
							lastStep?.addFinalWizardAction(() => Application.relogin());

							resolve();
						})
						.catch(reject)
					;
				});
			}

			return Promise.resolve();
		}

		#handleSaveValueTrigger(trigger, stepSwitchButtonType, fieldId, fieldType)
		{
			if (stepSwitchButtonType !== StepSwitchButtonType.CONTINUE)
			{
				return Promise.resolve();
			}

			if (fieldType === FieldType.PHONE_INPUT)
			{
				return saveFieldValue(this.fieldsValues.get(fieldId), fieldId, fieldType);
			}

			return Promise.resolve();
		}
	}

	module.exports = { FieldsTriggersHandler };
});
