/**
 * @module crm/conversion/wizard/step
 */
jn.define('crm/conversion/wizard/step', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ConversionWizardLayout } = require('crm/conversion/wizard/layout');
	const { WizardStep } = require('layout/ui/wizard/step');

	/**
	 * @class ConversionWizardStep
	 */
	class ConversionWizardStep extends WizardStep
	{
		constructor(props)
		{
			super(props);
			const { onFinish, onMoveToNext, ...restProps } = props;
			this.props = restProps;
			this.onFinish = onFinish;
		}

		getTitle()
		{
			return Loc.getMessage('MCRM_CONVERSION_WIZARD_STEP_TITLE');
		}

		getNextStepButtonText()
		{
			const { finalStep } = this.props;

			return Loc.getMessage(`MCRM_CONVERSION_WIZARD_STEP_${finalStep ? 'FINISH' : 'CONTINUE'}`);
		}

		onFinishStep()
		{
			if (this.onFinish)
			{
				this.onFinish();
			}
		}

		createLayout()
		{
			return new ConversionWizardLayout(this.props);
		}
	}

	module.exports = { ConversionWizardStep };
});
