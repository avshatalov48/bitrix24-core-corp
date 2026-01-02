/**
 * @module intranet/qualification/step-layout
 */
jn.define('intranet/qualification/step-layout', (require, exports, module) => {
	const { StepLayout: BaseStepLayout } = require('layout/ui/wizard/step-layout');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { ButtonSize, Button } = require('ui-system/form/buttons');
	const { Color } = require('tokens');

	class StepLayout extends BaseStepLayout
	{
		constructor(props)
		{
			super(props);

			this.state.isNextStepButtonEnabled = props.step.isNextStepButtonEnabled();
		}

		render()
		{
			const nextStepButtonText = this.step.getNextStepButtonText();

			return Box(
				{
					footer: BoxFooter(
						{
							keyboardButton: nextStepButtonText && {
								text: nextStepButtonText,
								disabled: !this.state.isNextStepButtonEnabled,
								testId: 'qualification-button-submit',
								onClick: () => this.wizard.moveToNextStep(),
							},
						},
						nextStepButtonText && Button({
							testId: 'qualification-button-next',
							stretched: true,
							size: ButtonSize.L,
							disabled: !this.state.isNextStepButtonEnabled,
							text: nextStepButtonText,
							onClick: () => this.wizard.moveToNextStep(),
						}),
					),
					safeArea: {
						bottom: true,
					},
					withScroll: true,
				},
				this.#renderProgressBar(),
				this.step.createLayout(),
			);
		}

		#renderProgressBar()
		{
			const { isEnabled, number, count } = this.step.getProgressBarSettings();

			if (!isEnabled)
			{
				return null;
			}

			return View({
				style: {
					height: 2,
					width: `${number / count * 100}%`,
					backgroundColor: Color.accentSoftBlue1.toHex(),
				},
			});
		}

		toggleChangeStepButton()
		{
			const isNextStepButtonEnabled = this.step.isNextStepButtonEnabled();

			this.setState({ isNextStepButtonEnabled });
		}
	}

	module.exports = { StepLayout };
});
