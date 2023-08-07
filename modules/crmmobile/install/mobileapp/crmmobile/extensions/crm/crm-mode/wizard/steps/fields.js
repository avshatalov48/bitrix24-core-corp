/**
 * @module crm/crm-mode/wizard/steps/fields
 */
jn.define('crm/crm-mode/wizard/steps/fields', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ConversionWizardFieldsLayout } = require('crm/conversion/wizard/layouts');
	const FIELDS = 'fields';

	/**
	 * @class ConversionFieldsStep
	 */
	class ConversionFieldsStep extends WizardStep
	{
		static getId()
		{
			return FIELDS;
		}

		getTitle()
		{
			return Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_FIELDS_NEXT_TITLE');
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_FIELDS_NEXT_BUTTON');
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

	module.exports = { ConversionFieldsStep, FIELDS };
});
