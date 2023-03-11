(() =>
{
	/**
	 * @class Wizard
	 */
	class Wizard extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.currentStepId = null;
			this.currentLayout = layout;

			this.isLoading = false;

			/** @type {Map<string,WizardStep>} */
			this.steps = new Map();

			layout.on("onViewShown", () => {
				this.onLayoutViewShown(layout, this.getStepIdByIndex(0));
				this.toggleTitle();
			});

			this.props.steps.forEach(stepId => {
				this.addStep(stepId, this.props.stepForId(stepId))
			});
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
			let currentStepId = this.getCurrentStepId();
			if (!currentStepId)
			{
				this.setCurrentStep(stepId);
				this.toggleTitle();
				this.toggleChangeStepButtons();
			}

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
			return this.steps.get(this.getCurrentStepId());
		}

		setCurrentStep(stepId)
		{
			if (this.steps.has(stepId))
			{
				if (this.getCurrentStepId())
				{
					this.getCurrentStep().onLeaveStep();
				}
				this.currentStepId = stepId;
				this.getCurrentStep().onEnterStep();
			}
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
			const moveToStepResult = this.getCurrentStep().onMoveToNextStep();

			this.processMoveToStepResult(moveToStepResult, () => {
				const newStepIndex = this.getStepIndexById(this.getCurrentStepId()) + 1;

				if (newStepIndex >= this.getTotalStepsCount())
				{
					layout.close();
				}
				else
				{
					this.openStepWidget(this.getStepIdByIndex(newStepIndex));
				}
			});
		}

		openStepWidget(stepId)
		{
			let step = this.steps.get(stepId);
			PageManager
				.openWidget("layout", {
					titleParams: {
						text: step.getTitle(),
						detailText: step.getSubTitle(),
					},
				})
				.then(layoutWidget => {
					layoutWidget.on('onViewShown', () =>
					{
						this.onLayoutViewShown(layoutWidget, stepId);
					});
					layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.showComponent(new class extends LayoutComponent
						{
							render()
							{
								return View(
									{
										style: {
											backgroundColor: '#eef2f4',
										},
									},
									step.createLayout({
										fieldFocusDelay: 500,
									})
								);
							}
						}
					);
				});
			;
		}

		onLayoutViewShown(layout, stepId)
		{
			this.currentLayout = layout;
			this.setCurrentStep(stepId);

			this.toggleChangeStepButtons();
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

				moveToStepResult.then(
					() =>
					{
						finishLoading();
						successfullyMovedCallback();
					},
					() =>
					{
						finishLoading();
					}
				);
			}
		}

		/**
		 * Set enabled/disabled state for prev/next wizard buttons
		 */
		toggleChangeStepButtons()
		{
			const isNextStepEnabled = this.getCurrentStep().isNextStepEnabled() && !this.isLoading;
			const nextStepButtonText = this.getCurrentStep().getNextStepButtonText();

			this.currentLayout.setRightButtons([
				{
					name: nextStepButtonText,
					type: 'text',
					...(isNextStepEnabled
							? {
								color: '#0B66C3',
								callback: this.moveToNextStep.bind(this),
							}
							: {}
					)
				}
			]);

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
			return View({
					style: {
						backgroundColor: '#eef2f4',
					},
				},
				this.getCurrentStepId() ? this.getCurrentStep().createLayout() : null,
			);
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
		onChangeStepAvailability(isNestStepEnabled)
		{
			this.toggleChangeStepButtons();
		}
	}

	this.Wizard = Wizard;
})();
