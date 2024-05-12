/**
 * @module layout/ui/wizard
 */
jn.define('layout/ui/wizard', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const BUTTON_COLORS = {
		ENABLED: AppTheme.colors.accentMainLinks,
		DISABLED: AppTheme.colors.base6,
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
		 * @param {Boolean} props.showNextStepButtonAtBottom
		 */
		constructor(props)
		{
			super(props);

			this.isLoading = false;
			this.currentStepId = null;
			this.currentLayout = this.getLayoutWidget();
			this.parentManager = this.getPageManager();

			this.stepLayoutRef = null;
			this.showNextStepButtonAtBottom = props.showNextStepButtonAtBottom || false;

			/** @type {Map<string,WizardStep>} */
			this.steps = new Map();

			const { steps, stepForId } = this.props;

			if (Array.isArray(steps) && steps.length > 0 && stepForId)
			{
				steps.forEach((stepId) => {
					this.addStep(stepId, stepForId(stepId));
				});

				const firstStep = this.getStepIdByIndex(0);
				this.setCurrentStep(firstStep);
				this.setLayoutParameters();
				this.onLayoutViewShown(this.currentLayout, firstStep);
			}
		}

		setLayoutParameters()
		{
			this.toggleTitle();
			this.toggleChangeStepButtons();
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
			return this.getStepById(this.getCurrentStepId());
		}

		getStepById(stepId)
		{
			return this.steps.get(stepId);
		}

		setCurrentStep(stepId)
		{
			if (!this.steps.has(stepId))
			{
				console.error(`not find step ${stepId}`);

				return;
			}

			this.currentStepId = stepId;
		}

		getNextStepIndex()
		{
			return this.getStepIndexById(this.getCurrentStepId()) + 1;
		}

		getPrevStepIndex()
		{
			return this.getStepIndexById(this.getCurrentStepId()) - 1;
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
			return [...this.steps.keys()].indexOf(stepId);
		}

		/**
		 * Get step id by its index (position)
		 *
		 * @param index int
		 * @returns {string}
		 */
		getStepIdByIndex(index)
		{
			return [...this.steps.keys()][index];
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
			const newStepIndex = this.getNextStepIndex();
			const nextStepId = this.getStepIdByIndex(newStepIndex);

			this.moveToStep(nextStepId);
		}

		moveToStep(stepId)
		{
			const currentStep = this.getCurrentStep();
			const moveToStepResult = currentStep.onMoveToNextStep(stepId);

			this.processMoveToStepResult(moveToStepResult, () => {
				currentStep.onLeaveStep(this.getCurrentStepId());

				const nextStepIndex = this.getNextStepIndex();
				if (nextStepIndex >= this.getTotalStepsCount())
				{
					this.onFinish();
				}
				else
				{
					this.openStepWidget(stepId);
					this.setCurrentStep(stepId);
				}

				const nextStepId = this.getStepIdByIndex(nextStepIndex);

				if (!nextStepId)
				{
					return;
				}

				const nextStep = this.getStepById(nextStepId);

				if (nextStep.isNeedToSkip())
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
						text: step.isNeedToSkip() ? null : step.getTitle(),
						detailText: step.isNeedToSkip() ? null : step.getSubTitle(),
					},
					animate: !step.isNeedToSkip(),
					backgroundColor: AppTheme.colors.bgSecondary,
				})
				.then((layoutWidget) => {
					if (!step.isPrevStepEnabled())
					{
						layoutWidget.setLeftButtons([]);
					}

					this.onLayoutViewShown(layoutWidget, stepId);

					layoutWidget.enableNavigationBarBorder(
						step.isNavigationBarBorderEnabled() === null
							? this.isNavigationBarBorderEnabled()
							: step.isNavigationBarBorderEnabled(),
					);
					layoutWidget.showComponent(new StepLayout({
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

		onFinish()
		{
			this.currentLayout.close(() => {
				this.getCurrentStep().onFinishStep();
			});
		}

		onLayoutViewShown(layout, stepId)
		{
			if (!layout)
			{
				throw new Error(`Could not find layout for stepId: ${stepId}`);
			}

			layout.on('onViewShown', () => {
				this.setLayoutWidget(layout);
				this.setCurrentStep(stepId);
				this.setLayoutParameters();
				this.onEnterStep(stepId);
			});
		}

		onEnterStep(stepId)
		{
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

				moveToStepResult.then((result = {}) => {
					const { finish = false, next = true } = result;
					this.isLoading = false;

					if (finish)
					{
						this.onFinish();

						return;
					}

					if (!next)
					{
						return;
					}

					successfullyMovedCallback();
				}).catch((e) => {
					console.error(e);
					this.isLoading = false;
					this.onFinish();
				});
			}
		}

		/**
		 * Set enabled/disabled state for prev/next wizard buttons
		 */
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

			if (this.showNextStepButtonAtBottom)
			{
				if (this.stepLayoutRef)
				{
					this.stepLayoutRef.toggleChangeStepButton(isEnabled);
				}
			}
			else
			{
				const nextStepButtonText = currentStep.getNextStepButtonText();

				this.currentLayout.setRightButtons([
					{
						name: nextStepButtonText,
						testId: 'wizardMoveToNextStepButton',
						type: 'text',
						color: isEnabled ? BUTTON_COLORS.ENABLED : BUTTON_COLORS.DISABLED,
						callback: () => {
							if (isEnabled)
							{
								this.moveToNextStep();
							}
						},
					},
				]);
			}

			const leftButtons = this.getPrevStepIndex() >= 0 && currentStep.isPrevStepEnabled() ? [
				{
					type: 'back',
					callback: async () => {
						const prevStepId = this.getStepIdByIndex(this.getPrevStepIndex());
						await currentStep.onMoveToBackStep(prevStepId);
						this.currentLayout.back();
					},
				},
			] : [];
			this.currentLayout.setLeftButtons(leftButtons);
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
				showNextStepButtonAtBottom: this.showNextStepButtonAtBottom,
				ref: (ref) => {
					this.stepLayoutRef = ref;
				},
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
