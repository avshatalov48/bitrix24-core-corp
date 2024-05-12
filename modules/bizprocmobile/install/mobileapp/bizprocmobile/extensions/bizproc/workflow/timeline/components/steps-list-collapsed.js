/**
 * @module bizproc/workflow/timeline/components/steps-list-collapsed
 * */
jn.define('bizproc/workflow/timeline/components/steps-list-collapsed', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { animate, parallel } = require('animation');
	const { Counter } = require('bizproc/workflow/timeline/components/counter');
	const { StepContent } = require('bizproc/workflow/timeline/components/step-content');
	const { StepWrapper } = require('bizproc/workflow/timeline/components/step-wrapper');
	const { dots } = require('bizproc/workflow/timeline/icons');
	const { PureComponent } = require('layout/pure-component');
	const { Type } = require('type');

	const StepsState = {
		COLLAPSED: 'collapsed',
		EXPANDING: 'expanding',
		EXPANDED: 'expanded',
	};

	const MIN_EXPANDING_TIME = 500;

	class StepsListCollapsed extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				stepsState: StepsState.COLLAPSED,
			};
			this.stepsHeight = null;
			this.stepsRef = null;
			this.collapsedStepRef = null;

			this.collapsedButtonRef = null;
			this.collapsedButtonAnimation = null;
		}

		/**
		 * @return {string}
		 */
		get text()
		{
			return Type.isStringFilled(this.props.text) ? this.props.text : null;
		}

		/**
		 * @return {string}
		 */
		get textColor()
		{
			return Type.isStringFilled(this.props.textColor) ? this.props.textColor : AppTheme.colors.base2;
		}

		/**
		 * @return {View[]}
		 */
		get steps()
		{
			return Type.isArray(this.props.steps) ? this.props.steps : [];
		}

		/**
		 * @return {{
		 * 		value: ?number,
		 * 		iconContent: ?string,
		 * 		backgroundColor: ?string,
		 * 		color: ?string,
		 * 		trunkColor: ?string,
		 * 		hasTail: ?boolean,
		 * 		tailColor: ?string,
		 * 		size: ?number
		 * 	}}
		 */
		get counterOptions()
		{
			return Type.isObjectLike(this.props.counterOptions) ? this.props.counterOptions : {};
		}

		render()
		{
			return View(
				{},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					this.renderItems(),
				),
				this.renderCollapsed(),
			);
		}

		renderCollapsed()
		{
			return View(
				{
					ref: (ref) => {
						this.collapsedStepRef = ref;
					},
					style: {
						display: this.isExpanded() ? 'none' : 'flex',
					},
				},
				StepWrapper(
					{},
					Counter({
						border: {
							width: 1,
							style: 'solid',
							color: AppTheme.colors.base4,
						},
						iconContent: dots(),
						trunkColor: AppTheme.colors.base4,
						tailColor: AppTheme.colors.base7,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						...this.counterOptions,
					}),
					StepContent(
						{},
						this.renderCollapsedButton(),
					),
				),
			);
		}

		renderCollapsedButton()
		{
			return View(
				{
					testId: this.props.collapsedButtonTestId,
					style: {
						minWidth: 139,
						height: 22,
						alignSelf: 'flex-start',
						justifyContent: 'center',
						position: 'relative',
						borderStyle: 'solid',
						borderColor: AppTheme.colors.base5,
						borderWidth: 1,
						borderRadius: 4,
						paddingHorizontal: 33,
					},
					onTouchesBegan: () => {
						if (this.collapsedButtonAnimation)
						{
							this.collapsedButtonAnimation.stop();
							this.collapsedButtonAnimation = null;
						}

						if (this.collapsedButtonRef)
						{
							this.collapsedButtonAnimation = this.collapsedButtonRef.animate(
								{
									duration: 100,
									opacity: 0.05,
								},
								() => {
									this.collapsedButtonAnimation = null;
								},
							);
						}
					},
					onTouchesEnded: () => {
						if (this.collapsedButtonAnimation)
						{
							this.collapsedButtonAnimation.stop();
							this.collapsedButtonAnimation = null;
						}

						if (this.collapsedButtonRef)
						{
							this.collapsedButtonAnimation = this.collapsedButtonRef.animate(
								{
									delay: 100,
									opacity: 0,
									duration: 100,
								},
								() => {
									this.collapsedButtonAnimation = null;
								},
							);
						}
					},
					onClick: () => this.toggleStepsState(),
				},
				View(
					{
						ref: (ref) => {
							this.collapsedButtonRef = ref;
						},
						style: {
							position: 'absolute',
							left: 0,
							right: 0,
							top: 0,
							bottom: 0,
							backgroundColor: AppTheme.colors.base1,
							opacity: 0,
						},
					},
				),
				Type.isStringFilled(this.text) && Text({
					text: this.text,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						color: AppTheme.colors.base2,
						textAlign: 'center',
						fontSize: 13,
						fontWeight: '500',
					},
				}),
			);
		}

		renderItems()
		{
			return View(
				{
					ref: (ref) => {
						this.stepsRef = ref;
					},
					onLayout: ({ height }) => {
						if (height > 0)
						{
							this.stepsHeight = height;
						}
					},
					style: {
						position: this.isCollapsed() ? 'absolute' : 'relative',
						height: this.isExpanding() ? 0 : null,
						width: '100%',
					},
				},
				...this.steps,
			);
		}

		toggleStepsState()
		{
			if (this.isCollapsed())
			{
				const onAnimationStarted = () => {
					if (this.props.onStepsExpanding && Type.isFunction(this.props.onStepsExpanding))
					{
						this.props.onStepsExpanding();
					}
				};

				const onAnimationOver = () => {
					this.setState({ stepsState: StepsState.EXPANDED });

					if (this.props.onStepsExpanded && Type.isFunction(this.props.onStepsExpanded))
					{
						this.props.onStepsExpanded();
					}
				};

				this.setState({ stepsState: StepsState.EXPANDING }, () => {
					const runAnimations = parallel(this.showSteps.bind(this), this.hideCollapsedStep.bind(this));

					onAnimationStarted();
					runAnimations()
						.then(onAnimationOver)
						.catch((err) => {
							console.error(err);
							onAnimationOver();
						})
					;
				});
			}
			else
			{
				this.setState({ stepsState: StepsState.COLLAPSED });
			}
		}

		/**
		 * @return {boolean}
		 */
		isExpanded()
		{
			return this.state.stepsState === StepsState.EXPANDED;
		}

		/**
		 * @return {boolean}
		 */
		isExpanding()
		{
			return this.state.stepsState === StepsState.EXPANDING;
		}

		/**
		 * @return {boolean}
		 */
		isCollapsed()
		{
			return this.state.stepsState === StepsState.COLLAPSED;
		}

		async showSteps()
		{
			await animate(this.stepsRef, {
				duration: (
					Type.isNumber(this.stepsHeight)
						? Math.max(this.stepsHeight, MIN_EXPANDING_TIME)
						: MIN_EXPANDING_TIME
				),
				height: Type.isNumber(this.stepsHeight) ? this.stepsHeight : device.screen.height,
				option: 'linear',
			});
		}

		async hideCollapsedStep()
		{
			await animate(this.collapsedStepRef, {
				duration: MIN_EXPANDING_TIME,
				opacity: 0,
				height: 0,
				option: 'linear',
			});
		}
	}

	module.exports = {
		StepsListCollapsed: (props) => new StepsListCollapsed(props),
	};
});
