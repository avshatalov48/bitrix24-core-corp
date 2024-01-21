/**
 * @module layout/ui/wizard/step-layout
 */
jn.define('layout/ui/wizard/step-layout', (require, exports, module) => {

	const AppTheme = require('apptheme');
	const { ProgressBar } = require('layout/ui/wizard/progress-bar');
	const { PureComponent } = require('layout/pure-component');

	const BUTTON_COLORS = {
		ENABLED: AppTheme.colors.accentMainLinks,
		DISABLED: AppTheme.colors.base6,
	};

	/**
	 * @class StepLayout
	 */
	class StepLayout extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {Boolean} props.showNextStepButtonAtBottom
		 */
		constructor(props)
		{
			super(props);

			this.state.isNeedToRender = !props.step.isNeedToSkip();
			this.state.isNextStepEnabled = props.step.isNextStepEnabled();

			this.showNextStepButtonAtBottom = props.showNextStepButtonAtBottom || false;
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

					if (!this.showNextStepButtonAtBottom)
					{
						layoutWidget.setRightButtons([
							{
								name: this.step.getNextStepButtonText(),
								testId: 'wizardMoveToNextStepButton',
								type: 'text',
								color: BUTTON_COLORS.ENABLED,
								callback: this.wizard.moveToNextStep.bind(this.wizard),
							},
						]);
					}

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
					resizableByKeyboard: this.step.resizableByKeyboard(),
					style: {
						height: '100%',
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
			const isEnabled = this.state.isNextStepEnabled;

			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgSecondary,
						height: 72,
						borderTopWidth: 1,
						borderTopColor: AppTheme.colors.bgSeparatorPrimary,
					},
				},
				Button({
					testId: 'wizardMoveToNextStepBottomButton',
					style: {
						borderRadius: 10,
						height: 48,
						marginTop: 12,
						marginRight: 18,
						marginBottom: 6,
						marginLeft: 18,
						color: isEnabled ? AppTheme.colors.baseWhiteFixed : AppTheme.colors.base5,
						backgroundColor: isEnabled ? AppTheme.colors.accentMainPrimary : AppTheme.colors.base7,
						fontSize: 17,
						fontWeight: '500',
						textAlign: 'center',
					},
					numberOfLines: 1,
					text: this.step.getNextStepButtonText(),
					onClick: () => {
						if (isEnabled)
						{
							this.wizard.moveToNextStep();
						}
					},
				}),
			);
		}

		toggleChangeStepButton(isEnabled = true)
		{
			this.setState({
				isNextStepEnabled: isEnabled,
				isNeedToRender: true,
			});
		}
	}

	module.exports = { StepLayout };
});
