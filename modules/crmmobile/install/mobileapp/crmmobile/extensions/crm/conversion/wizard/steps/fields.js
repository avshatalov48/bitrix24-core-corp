/**
 * @module crm/conversion/wizard/steps/fields
 */
jn.define('crm/conversion/wizard/steps/fields', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ConversionWizardFieldsLayout } = require('crm/conversion/wizard/layouts');
	const FIELDS = 'fields-step';

	/**
	 * @class ConversionWizardFieldsStep
	 */
	class ConversionWizardFieldsStep extends WizardStep
	{
		static getId()
		{
			return FIELDS;
		}

		getTitle()
		{
			return Loc.getMessage('MCRM_CONVERSION_WIZARD_STEPS_FIELDS_TITLE');
		}

		getMediumPositionHeight()
		{
			const { getFieldsConfig } = this.props;
			const margins = 85;
			const lineMargins = 10;
			const headerHeight = 115;
			const fieldHeight = 44;
			const booleanFieldHeight = 52;
			const fieldsConfig = getFieldsConfig();

			return fieldsConfig.reduce(
				(sum, { data }) => sum + data.reduce(
					(fieldSum, { entityTypeName }) => fieldSum + (entityTypeName ? booleanFieldHeight : fieldHeight),
					0,
				),
				(lineMargins * 2) + headerHeight + margins,
			);
		}

		onMoveToBackStep(stepId)
		{
			return this.props.onMoveToBackStep(stepId);
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('MCRM_CONVERSION_WIZARD_STEPS_NEXT_BUTTON');
		}

		onFinishStep()
		{
			this.props.onFinish();
		}

		createLayout()
		{
			return new ConversionWizardFieldsLayout(this.props);
		}
	}

	module.exports = { ConversionWizardFieldsStep, FIELDS };
});
