/**
 * @module crm/crm-mode/wizard/steps/conversion
 */
jn.define('crm/crm-mode/wizard/steps/conversion', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ConversionLayout } = require('crm/crm-mode/wizard/layouts');
	const CONVERSION = 'conversion';

	/**
	 * @class ConversionStep
	 */
	class ConversionStep extends WizardStep
	{
		static getId()
		{
			return CONVERSION;
		}

		getTitle()
		{
			return Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_TITLE');
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_CONVERSION_NEXT_BUTTON');
		}

		onMoveToNextStep()
		{
			return this.props.onMoveToNextStep();
		}

		onFinishStep()
		{
			this.props.onFinish();
		}

		createLayout()
		{
			return new ConversionLayout(this.props);
		}
	}

	module.exports = { ConversionStep, CONVERSION };
});
