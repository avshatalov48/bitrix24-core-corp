/**
 * @module sign/master/master-wizard
 */
jn.define('sign/master/master-wizard', (require, exports, module) => {
	const { MasterStepLayout } = require('sign/master/master-step-layout');
	const { Wizard } = require('layout/ui/wizard');
	const { Color } = require('tokens');

	/**
	 * @class MasterWizard
	 */
	class MasterWizard extends Wizard
	{
		openStepWidget(stepId)
		{
			const step = this.steps.get(stepId);

			this.parentManager
				.openWidget('layout', {
					titleParams: {
						text: step.isNeedToSkip() ? null : step.getTitle(),
						detailText: step.isNeedToSkip() ? null : step.getSubTitle(),
					},
					resizableByKeyboard: true,
					animate: !step.isNeedToSkip(),
					backgroundColor: Color.bgContentPrimary.toHex(),
				})
				.then((layoutWidget) => {
					this.onLayoutViewShown(layoutWidget, stepId);

					layoutWidget.enableNavigationBarBorder(
						step.isNavigationBarBorderEnabled() === null
							? this.isNavigationBarBorderEnabled()
							: step.isNavigationBarBorderEnabled(),
					);
					layoutWidget.showComponent(new MasterStepLayout({
						layoutWidget,
						step,
						wizard: this,
						showNextStepButtonAtBottom: this.showNextStepButtonAtBottom,
						ref: (ref) => {
							if (ref)
							{
								this.stepLayoutRef = ref;
							}
						},
					}));
				}).catch(console.error);
		}

		toggleChangeStepButtons(isNextStepEnabled = true)
		{
			const currentStep = this.getCurrentStep();
			const isNeedToSkip = currentStep.isNeedToSkip();
			const isNeedToShowNextStep = currentStep.isNeedToShowNextStep();

			if (isNeedToSkip || !isNeedToShowNextStep)
			{
				return;
			}

			const isEnabled = currentStep.isNextStepEnabled() && isNextStepEnabled && !this.isLoading;

			if (this.showNextStepButtonAtBottom && this.stepLayoutRef)
			{
				this.stepLayoutRef.toggleChangeStepButton(isEnabled);
			}

			const leftButtons = currentStep.getLeftButtons();
			const rightButtons = currentStep.getRightButtons();

			if (leftButtons)
			{
				this.currentLayout.setLeftButtons(leftButtons);
			}
			this.currentLayout.setRightButtons(rightButtons);
		}

		render()
		{
			return new MasterStepLayout({
				step: this.getCurrentStep(),
				wizard: this,
				showNextStepButtonAtBottom: this.showNextStepButtonAtBottom,
				resizableByKeyboard: true,
				ref: (ref) => {
					this.stepLayoutRef = ref;
				},
			});
		}
	}

	module.exports = { MasterWizard };
});
