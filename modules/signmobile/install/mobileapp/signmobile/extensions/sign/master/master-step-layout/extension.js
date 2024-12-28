/**
 * @module sign/master/master-step-layout
 */
jn.define('sign/master/master-step-layout', (require, exports, module) => {
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { StepLayout } = require('layout/ui/wizard/step-layout');
	const { ProgressBar } = require('layout/ui/wizard/progress-bar');
	const { Color } = require('tokens');

	/**
	 * @class MasterStepLayout
	 */
	class MasterStepLayout extends StepLayout
	{
		constructor(props)
		{
			super(props);

			this.state.isNextButtonLoading = false;
			this.state.isCloseButtonDisable = false;
			this.state.isCloseButtonShow = false;
		}

		isLastStep()
		{
			return this.step.props.stepNumber === this.step.props.totalSteps;
		}

		#closeKeyboard = () => {
			Keyboard.dismiss();
		};

		render()
		{
			if (!this.state.isNeedToRender)
			{
				return null;
			}

			return View(
				{
					resizableByKeyboard: this.step.resizableByKeyboard(),
					clickable: true,
					onClick: this.#closeKeyboard,
					style: {
						flex: 1,
					},
					safeArea: {
						bottom: this.showNextStepButtonAtBottom,
					},
				},
				new ProgressBar({ step: this.step }),
				View(
					{
						style: {
							flex: 1,
						},
					},
					this.step.createLayout({
						fieldFocusDelay: 500,
					}),
				),
				this.showNextStepButtonAtBottom && this.renderNextStepBottomButton(),
			);
		}

		renderNextStepBottomButton()
		{
			const isNextStepEnabled = this.state.isNextStepEnabled;

			const onclick = () => {
				if (isNextStepEnabled)
				{
					this.wizard.moveToNextStep();
				}
			};

			return View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
						height: 62,
						marginTop: 12,
						marginRight: 18,
						marginBottom: 6,
						marginLeft: 18,
					},
				},
				Button({
					text: this.step.getNextStepButtonText(),
					testId: 'Button',
					size: ButtonSize.XL,
					design: ButtonDesign.FILLED,
					stretched: true,
					disabled: !isNextStepEnabled,
					onClick: onclick,
				}),
			);
		}
	}

	module.exports = { MasterStepLayout };
});
