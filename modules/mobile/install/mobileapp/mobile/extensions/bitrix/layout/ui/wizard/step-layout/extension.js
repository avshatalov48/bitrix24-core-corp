/**
 * @module layout/ui/wizard/step-layout
 */
jn.define('layout/ui/wizard/step-layout', (require, exports, module) => {

	const { ProgressBar } = require('layout/ui/wizard/progress-bar');
	const { PureComponent } = require('layout/pure-component');

	const BUTTON_COLORS = {
		ENABLED: '#0065a3',
		DISABLED: '#d5d7db',
	};

	/**
	 * @class StepLayout
	 */
	class StepLayout extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state.isNeedToRender = !props.step.isNeedToSkip();
		}

		get step()
		{
			return BX.prop.get(this.props, 'step', {});
		}

		get wizard()
		{
			return BX.prop.get(this.props, 'wizard', {});
		}

		componentDidMount()
		{
			const { layoutWidget } = this.props;

			if (!this.state.isNeedToRender && layoutWidget)
			{
				setTimeout(() => {
					layoutWidget.setTitle({
						text: this.step.getTitle(),
						detailText: this.step.getSubTitle(),
					});
					layoutWidget.setRightButtons([
						{
							name: this.step.getNextStepButtonText(),
							testId: 'wizardMoveToNextStepButton',
							type: 'text',
							color: BUTTON_COLORS.ENABLED,
							callback: this.wizard.moveToNextStep.bind(this.wizard),
						},
					]);
					this.setState({ isNeedToRender: true });
				}, Application.getPlatform() === 'ios' ? 0 : 400);
			}
		}

		render()
		{
			if (!this.state.isNeedToRender)
			{
				return null;
			}

			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
						height: '100%',
					},
				},
				new ProgressBar({ step: this.step }),
				View(
					{
						style: {
							flexShrink: 1,
						},
					},
					this.step.createLayout({
						fieldFocusDelay: 500,
					}),
				),
			);
		}
	}

	module.exports = { StepLayout };
});
