/**
 * @module layout/ui/wizard
 */
jn.define('layout/ui/wizard', (require, exports, module) => {

	const BUTTON_COLORS = {
		ENABLED: '#0065a3',
		DISABLED: '#d5d7db',
	};

	const { StepLayout } = require('layout/ui/wizard/step-layout');

	/**
	 * @class Wizard
	 */
	class Wizard extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Object[]} props.steps
		 * @param {Object} props.stepForId
		 * @param {Object} props.parentLayout
		 */
		constructor(props)
		{
			super(props);

			this.currentStepId = null;
			this.currentLayout = this.getLayoutWidget();
			this.parentManager = this.getPageManager();
			this.isLoading = false;

			/** @type {Map<string,WizardStep>} */
			this.steps = new Map();

			const { steps, stepForId } = this.props;

			if (Array.isArray(steps) && stepForId)
			{
				steps.forEach((stepId) => {
					this.addStep(stepId, stepForId(stepId));
				});
			}

			this.stepLayouts = new Map();
			this.stepLayouts.set(this.getStepIdByIndex(0), this.currentLayout);

			this.currentLayout.on('onViewShown', () => {
				this.onLayoutViewShown(this.getStepIdByIndex(0));
				this.toggleTitle();
			});
		}

		getLayoutWidget()
		{
			const { parentLayout } = this.props;

			if (parentLayout)
			{
				return parentLayout;
			}

			throw new Error('Wizard: missing required props.parentLayout');
		}

		getPageManager()
		{
			const { parentLayout } = this.props;

			return parentLayout || PageManager;
		}

		setLayoutWidget(layoutWidget)
		{
			this.currentLayout = layoutWidget;
		}

		/**
		 * Add step to wizard
		 *
		 * @param stepId string
		 * @param step Step
		 * @returns {Wizard}
		 */
		addStep(stepId, step)
		{
			this.steps.set(stepId, step);
			const currentStepId = this.getCurrentStepId();

			if (!currentStepId)
			{
				this.setCurrentStep(stepId);
				this.toggleTitle();
				this.toggleChangeStepButtons();
			}

			step
				.setTitleChangeHandler(this.onChangeTitle.bind(this))
				.setStepAvailabilityChangeHandler(this.onChangeStepAvailability.bind(this));

			return this;
		}

		/**
		 * Get current step
		 *
		 * @returns {WizardStep}
		 */
		getCurrentStep()
		{
			return this.steps.get(this.getCurrentStepId());
		}

		setCurrentStep(stepId)
		{
			if (!this.steps.has(stepId))
			{
				console.error(`not find step ${stepId}`);
				return null;

			}

			this.currentStepId = stepId;
		}

		/**
		 * Get current step id
		 *
		 * @returns {null|string}
		 */
		getCurrentStepId()
		{
			return this.currentStepId;
		}

		/**
		 * Get current step index (position) by step id
		 * @param stepId
		 * @returns {number}
		 */
		getStepIndexById(stepId)
		{
			return Array.from(this.steps.keys()).indexOf(stepId);
		}

		/**
		 * Get step id by its index (position)
		 *
		 * @param index int
		 * @returns {string}
		 */
		getStepIdByIndex(index)
		{
			return Array.from(this.steps.keys())[index];
		}

		/**
		 * Total steps count
		 *
		 * @returns {number}
		 */
		getTotalStepsCount()
		{
			return this.steps.size;
		}

		/**
		 * Try to move wizard to next step
		 */
		moveToNextStep()
		{
			const newStepIndex = this.getStepIndexById(this.getCurrentStepId()) + 1;
			const nextStepId = this.getStepIdByIndex(newStepIndex);
			const moveToStepResult = this.getCurrentStep().onMoveToNextStep(nextStepId);

			this.processMoveToStepResult(moveToStepResult, () => {

				this.getCurrentStep().onLeaveStep(this.getCurrentStepId());
				this.getCurrentStep().onEnterStep(nextStepId);

				if (newStepIndex >= this.getTotalStepsCount())
				{
					this.onFinish();
				}
				else
				{
					this.openStepWidget(nextStepId);
					this.setCurrentStep(nextStepId);
				}

				if (this.getCurrentStep().isNeedToSkip())
				{
					this.moveToNextStep();
				}
			});
		}

		openStepWidget(stepId)
		{
			const step = this.steps.get(stepId);

			this.parentManager
				.openWidget('layout', {
					titleParams: {
						text: !step.isNeedToSkip() ? step.getTitle() : null,
						detailText: !step.isNeedToSkip() ? step.getSubTitle() : null,
					},
					animate: !step.isNeedToSkip(),
					backgroundColor: '#eef2f4',
				})
				.then((layoutWidget) => {
					if (!step.isPrevStepEnabled())
					{
						layoutWidget.setLeftButtons([]);
					}
					layoutWidget.on('onViewShown', () => {
						this.stepLayouts.set(stepId, layoutWidget);
						this.onLayoutViewShown(stepId);
					});
					layoutWidget.enableNavigationBarBorder(this.isNavigationBarBorderEnabled());
					layoutWidget.showComponent(new StepLayout({
						layoutWidget,
						step,
						wizard: this,
					}));
				});
		}

		onFinish()
		{
			this.currentLayout.close(() => {
				this.getCurrentStep().onFinishStep();
			});
		}

		onLayoutViewShown(stepId)
		{
			const layout = this.stepLayouts.get(stepId);
			if (!layout)
			{
				throw new Error(`Could not find layout for stepId: ${stepId}`);
			}

			this.setLayoutWidget(layout);
			this.setCurrentStep(stepId);
			this.toggleChangeStepButtons();
			this.getCurrentStep().onEnterStep(stepId);
		}

		isNavigationBarBorderEnabled()
		{
			if (this.props.isNavigationBarBorderEnabled === undefined)
			{
				return false;
			}

			return this.props.isNavigationBarBorderEnabled;
		}

		/**
		 * Process move to step result.
		 * If moveToStepResult is positive, then execute successfullyMovedCallback
		 *
		 * @param moveToStepResult
		 * @param successfullyMovedCallback
		 */
		processMoveToStepResult(moveToStepResult, successfullyMovedCallback)
		{
			if (moveToStepResult === true)
			{
				successfullyMovedCallback();
			}
			if (moveToStepResult && typeof moveToStepResult.then === 'function')
			{
				this.isLoading = true;
				setTimeout(() => { // loader will be shown with delay to avoid blinking if promise will resolved too fast
					if (this.isLoading)
					{
						dialogs.showLoadingIndicator({
							type: 'loading',
						});
					}
				}, 300);
				this.toggleChangeStepButtons();

				const finishLoading = () => {
					this.isLoading = false;
					dialogs.hideLoadingIndicator();
					this.toggleChangeStepButtons();
				};

				moveToStepResult.then((result = {}) => {
					const { finish } = result;
					const callback = finish ? this.onFinish.bind(this) : successfullyMovedCallback;

					finishLoading();
					callback();
				}).catch((e) => {
					console.error(e);
					this.onFinish();
				});
			}
		}

		/**
		 * Set enabled/disabled state for prev/next wizard buttons
		 */
		toggleChangeStepButtons(isNextStepEnabled = true)
		{
			if (this.getCurrentStep().isNeedToSkip())
			{
				return;
			}

			if (this.getCurrentStep().isNeedToShowNextStep())
			{
				const isEnabled = this.getCurrentStep().isNextStepEnabled() && !this.isLoading && isNextStepEnabled;
				const nextStepButtonText = this.getCurrentStep().getNextStepButtonText();

				this.currentLayout.setRightButtons([
					{
						name: nextStepButtonText,
						type: 'text',
						...(isEnabled
								? {
									color: BUTTON_COLORS.ENABLED,
									callback: this.moveToNextStep.bind(this),
								}
								: {
									color: BUTTON_COLORS.DISABLED,
									callback: () => {},
								}
						),
					},
				]);
			}
		}

		/**
		 * Update title and subtitle
		 */
		toggleTitle()
		{
			this.currentLayout.setTitle({
				text: this.getCurrentStep().getTitle(),
				detailText: this.getCurrentStep().getSubTitle(),
			});
		}

		render()
		{
			return new StepLayout({
				step: this.getCurrentStep(),
				wizard: this,
			});
		}

		/**
		 * If Step changes title, this callback will be executed
		 */
		onChangeTitle()
		{
			this.toggleTitle();
		}

		/**
		 * If Step changes next button availability, this callback will be executed
		 */
		onChangeStepAvailability(isNextStepEnabled)
		{
			this.toggleChangeStepButtons(isNextStepEnabled);
		}
	}

	module.exports = { Wizard };
});
