/**
 * @module bizproc/workflow/starter/description-step
 */
jn.define('bizproc/workflow/starter/description-step', (require, exports, module) => {
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('bizproc/wizard/progress-bar-number');
	const { DescriptionStepView } = require('bizproc/workflow/starter/description-step/view');

	class DescriptionStep extends WizardStep
	{
		constructor(props)
		{
			super(props);

			this.templateId = props.templateId || null;
			this.name = props.name || null;
			this.formattedTime = props.formattedTime || null;
			this.description = props.description || null;
			this.hasParameters = props.hasParameters ?? true;
			this.isConstantsTuned = props.isConstantsTuned ?? true;
		}

		updateProps(props)
		{
			this.templateId = props.templateId === undefined ? this.templateId : props.templateId;
			this.name = props.name === undefined ? this.name : props.name;
			this.description = props.description === undefined ? this.description : props.description;
			this.hasParameters = props.hasParameters === undefined ? this.hasParameters : props.hasParameters;
			this.isConstantsTuned = props.isConstantsTuned === undefined ? this.isConstantsTuned : props.isConstantsTuned;
			this.formattedTime = props.formattedTime === undefined ? this.formattedTime : props.formattedTime;
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				isCompleted: true,
				number: this.props.stepNumber,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: { text: Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_TITLE') },
				number: this.props.stepNumber,
				count: this.hasParameters ? this.props.totalSteps : this.props.totalSteps - 1,
			};
		}

		getTitle()
		{
			return this.props.title || '';
		}

		getNextStepButtonText()
		{
			return (
				this.hasParameters
					? Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_NEXT_STEP_BUTTON_TEXT')
					: Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_NEXT_STEP_BUTTON_FINISH_TEXT')
			);
		}

		isNextStepEnabled()
		{
			return true;
		}

		isPrevStepEnabled()
		{
			return true;
		}

		async onMoveToNextStep(stepId)
		{
			if (!this.isConstantsTuned)
			{
				NotifyManager.showError(Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_ERROR_CONSTANTS_NOT_TUNED'));

				return Promise.resolve({ finish: false, next: false });
			}

			if (this.hasParameters)
			{
				return Promise.resolve({ finish: false, next: true });
			}

			await NotifyManager.showLoadingIndicator();

			return new Promise((resolve) => {
				BX.ajax.runAction(
					'bizprocmobile.Workflow.start',
					{
						data: {
							signedDocument: this.props.signedDocument,
							templateId: this.templateId,
						},
					},
				)
					.then(() => {
						NotifyManager.hideLoadingIndicator(
							true,
							Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_SUCCESS_MESSAGE'),
							1500,
						);
						setTimeout(() => resolve({ finish: true, next: false }), 1500);
					})
					.catch((response) => {
						console.error(response.errors);
						NotifyManager.hideLoadingIndicator(false);
						if (Array.isArray(response.errors))
						{
							NotifyManager.showErrors(response.errors);
						}

						resolve({ finish: false, next: false });
					})
				;
			});
		}

		createLayout(props)
		{
			return new DescriptionStepView({
				name: this.name,
				description: this.description,
				formattedTime: this.formattedTime,
			});
		}
	}

	module.exports = { DescriptionStep };
});
