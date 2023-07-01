/**
 * @module crm/crm-mode/wizard/steps/mode
 */
jn.define('crm/crm-mode/wizard/steps/mode', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ModeLayout, MODES } = require('crm/crm-mode/wizard/layouts');
	const MODE = 'mode';

	/**
	 * @class ModeStep
	 */
	class ModeStep extends WizardStep
	{
		constructor(props)
		{
			super(props);

			this.modeLayout = new ModeLayout(props);
		}

		static getId()
		{
			return MODE;
		}

		onMoveToNextStep()
		{
			const { onMoveToNextStep, onClose, mode } = this.props;

			if (mode === this.modeLayout.getActiveMode())
			{
				onClose();

				return false;
			}

			return onMoveToNextStep();
		}

		getTitle()
		{
			return Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_MODE_TITLE');
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('MCRM_CRM_MODE_LAYOUTS_MODE_NEXT_BUTTON');
		}

		onFinishStep()
		{
			this.props.onFinish();
		}

		createLayout()
		{
			return this.modeLayout;
		}
	}

	module.exports = { ModeStep, MODE, MODES };
});
