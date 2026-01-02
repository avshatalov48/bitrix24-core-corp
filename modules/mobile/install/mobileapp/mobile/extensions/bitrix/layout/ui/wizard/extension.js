/**
 * @module layout/ui/wizard
 */
jn.define('layout/ui/wizard', (require, exports, module) => {
	const { Color } = require('tokens');
	const { StepLayout } = require('layout/ui/wizard/step-layout');
	const { StepSwitchButtonType } = require('layout/ui/wizard/const');
	const { Type } = require('type');

	/**
	 * @class Wizard
	 */
	class Wizard extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Object[]} props.steps
		 * @param {Function} props.stepForId
		 * @param {Object} [props.stepLayoutClass=StepLayout]
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

			this.stepLayoutRefs = new Map();
			this.showNextStepButtonAtBottom = props.showNextStepButtonAtBottom || false;

			/** @type {Map<string,WizardStep>} */
			this.steps = new Map();

			/** @type {Map<string,WizardStep>} */
			this.allSteps = new Map();

			const { steps, stepForId } = this.props;

			if (Type.isArrayFilled(steps) && stepForId)
			{
				steps.forEach((stepId) => {
					const step = stepForId(stepId, this);
					if (step.isActive())
					{
						this.addStep(stepId, step);
					}
					this.allSteps.set(stepId, step);
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

		getStepLayoutClass()
		{
			return this.props.stepLayoutClass || StepLayout;
		}

		activateStep(stepId)
		{
			if (!this.allSteps.has(stepId) || this.steps.has(stepId))
			{
				return;
			}

			const stepIdToAddAfter = this.#findStepIdToAddStepAfter(stepId);
			if (!stepIdToAddAfter)
			{
				return;
			}

			this.#addStepAfterStepId(stepId, this.allSteps.get(stepId), stepIdToAddAfter);
		}

		#findStepIdToAddStepAfter(stepIdToAdd)
		{
			const allStepIds = [...this.allSteps.keys()];

			let stepIdToAddAfter = null;
			let stepIndex = allStepIds.indexOf(stepIdToAdd);
			while (stepIndex > this.getStepIndexById(this.currentStepId))
			{
				stepIdToAddAfter = allStepIds[stepIndex - 1];

				if (this.steps.has(stepIdToAddAfter))
				{
					break;
				}

				stepIndex -= 1;
			}

			return stepIdToAddAfter;
		}

		#addStepAfterStepId(stepId, step, stepIdToAddAfter)
		{
			const stepsBefore = [];
			const stepsAfter = [];
			const stepIndexToAddAfter = [...this.steps.keys()].indexOf(stepIdToAddAfter);

			[...this.steps.entries()].forEach((entry, index) => {
				if (index <= stepIndexToAddAfter)
				{
					stepsBefore.push(entry);
				}
				else
				{
					stepsAfter.push(entry);
				}
			});

			step
				.setTitleChangeHandler(this.onChangeTitle.bind(this))
				.setStepAvailabilityChangeHandler(this.onChangeStepAvailability.bind(this))
			;
			this.steps = new Map([...stepsBefore, [stepId, step], ...stepsAfter]);
		}

		deactivateStep(stepId)
		{
			if (!this.allSteps.has(stepId) || !this.steps.has(stepId))
			{
				return;
			}

			this.steps.delete(stepId);
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
				.setStepAvailabilityChangeHandler(this.onChangeStepAvailability.bind(this))
			;

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
		 *
		 * @param {StepSwitchButtonType} [stepSwitchButtonType=StepSwitchButtonType.CONTINUE]
		 */
		moveToNextStep(stepSwitchButtonType = StepSwitchButtonType.CONTINUE)
		{
			this.getCurrentStep().onBeforeMoveToNextStep(stepSwitchButtonType);

			const newStepIndex = this.getNextStepIndex();
			const nextStepId = this.getStepIdByIndex(newStepIndex);

			this.moveToStep(nextStepId, stepSwitchButtonType);
		}

		moveToStep(stepId, stepSwitchButtonType)
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
					this.moveToNextStep(stepSwitchButtonType);
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
						type: step.isNeedToSkip() ? null : step.getTitleType(),
					},
					animate: !step.isNeedToSkip(),
					backgroundColor: step.getBackgroundColor(),
				})
				.then((layoutWidget) => {
					step.setLayoutWidget(layoutWidget);

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

					const StepLayoutClass = this.getStepLayoutClass();

					layoutWidget.showComponent(
						new StepLayoutClass({
							layoutWidget,
							step,
							wizard: this,
							showNextStepButtonAtBottom: this.showNextStepButtonAtBottom,
							ref: (ref) => {
								if (ref)
								{
									this.stepLayoutRefs.set(stepId, ref);
								}
							},
						}),
					);
				})
				.catch(console.error)
			;
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
				this.stepLayoutRefs.get(this.getCurrentStepId())?.toggleChangeStepButton(isEnabled);
				this.#setSkipStepTopButton(isEnabled);
			}
			else
			{
				this.#setNextStepTopButton(isEnabled);
			}

			this.#setBackTopButton();
		}

		#setNextStepTopButton(isEnabled)
		{
			this.currentLayout.setRightButtons([
				{
					name: this.getCurrentStep().getNextStepButtonText(),
					testId: 'wizardMoveToNextStepButton',
					type: 'text',
					color: isEnabled ? Color.accentMainLinks.toHex() : Color.base6.toHex(),
					callback: () => {
						if (isEnabled)
						{
							this.moveToNextStep(StepSwitchButtonType.CONTINUE);
						}
					},
				},
			]);
		}

		#setSkipStepTopButton(isEnabled)
		{
			if (!this.getCurrentStep().canSkip())
			{
				return;
			}

			this.currentLayout.setRightButtons([
				{
					name: this.getCurrentStep().getSkipStepButtonText(),
					testId: 'wizardSkipStepButton',
					type: 'text',
					color: isEnabled ? Color.accentMainPrimaryalt.toHex() : Color.base6.toHex(),
					callback: () => {
						if (isEnabled)
						{
							this.moveToNextStep(StepSwitchButtonType.SKIP);
						}
					},
				},
			]);
		}

		#setBackTopButton()
		{
			const prevStepIndex = this.getPrevStepIndex();
			const currentStep = this.getCurrentStep();

			if (prevStepIndex < 0 || !currentStep.isPrevStepEnabled())
			{
				this.currentLayout.setLeftButtons([]);

				return;
			}

			this.currentLayout.setLeftButtons([
				{
					type: 'back',
					callback: async () => {
						await currentStep.onMoveToBackStep(this.getStepIdByIndex(prevStepIndex));
						this.currentLayout.back();
					},
				},
			]);
		}

		/**
		 * Update title and subtitle
		 */
		toggleTitle()
		{
			const currentStep = this.getCurrentStep();

			this.currentLayout.setTitle({
				text: currentStep.getTitle(),
				detailText: currentStep.getSubTitle(),
				type: currentStep.getTitleType(),
			});
		}

		render()
		{
			const StepLayoutClass = this.getStepLayoutClass();

			return new StepLayoutClass({
				step: this.getCurrentStep(),
				wizard: this,
				showNextStepButtonAtBottom: this.showNextStepButtonAtBottom,
				ref: (ref) => this.stepLayoutRefs.set(this.getCurrentStepId(), ref),
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
