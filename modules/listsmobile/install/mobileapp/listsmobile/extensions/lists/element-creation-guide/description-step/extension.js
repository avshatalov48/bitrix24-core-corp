/**
 * @module lists/element-creation-guide/description-step
 */
jn.define('lists/element-creation-guide/description-step', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('lists/wizard/progress-bar-number');
	const { EventEmitter } = require('event-emitter');
	const { DescriptionStepComponent } = require('lists/element-creation-guide/description-step/component');
	const { NotifyManager } = require('notify-manager');

	class DescriptionStep extends WizardStep
	{
		/**
		 * @param {Object} props
		 * @param {String} props.title
		 * @param {String} props.uid
		 * @param {Number} props.stepNumber
		 * @param {Number} props.totalSteps
		 * @param {Object} props.layout
		 * @param {Number} props.iBlockId
		 * @param {String} props.name
		 * @param {String} props.formattedTime
		 */
		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.iBlockId = this.props.iBlockId || null;
			this.name = this.props.name || null;
			this.formattedTime = this.props.formattedTime || null;

			this.prevIBlockId = null;
			this.prevDescription = null;

			this.description = null;
			this.hasFieldsToRender = true;
			this.isConstantsTuned = false;
			this.sign = '';
			this.startTime = null;

			this.subscribeOnEvents();
		}

		subscribeOnEvents()
		{
			this.customEventEmitter.on(
				'DescriptionStepComponent:onAfterLoadDescription',
				({ description, hasFieldsToRender, sign, isConstantsTuned, hasErrors }) => {
					this.description = description;
					this.startTime = this.getCurrentTime();
					if (this.prevDescription === null)
					{
						this.prevDescription = this.description;
					}
					this.hasFieldsToRender = hasFieldsToRender;
					this.isConstantsTuned = isConstantsTuned;
					this.sign = sign;

					this.customEventEmitter.emit(
						'DescriptionStep:onAfterLoadDescription',
						[this.startTime],
					);
					this.stepAvailabilityChangeCallback(!hasErrors);

					if (hasErrors)
					{
						this.description = null;
						this.prevDescription = null;
						this.hasFieldsToRender = true;
						this.isConstantsTuned = true;
						this.sign = null;
					}
				},
			);
		}

		get isLoaded()
		{
			return (this.description !== null);
		}

		get isPreviousSelectedIBlockId()
		{
			return this.iBlockId === this.prevIBlockId;
		}

		getCurrentTime()
		{
			return Math.round(Date.now() / 1000);
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (this.isPreviousSelectedIBlockId)
			{
				this.description = this.prevDescription;
				this.stepAvailabilityChangeCallback(true);
			}

			if (!this.isLoaded)
			{
				this.hasFieldsToRender = true;
				this.isConstantsTuned = false;
				this.sign = '';
				this.stepAvailabilityChangeCallback(false);
			}
		}

		onMoveToBackStep(stepId)
		{
			this.prevIBlockId = this.iBlockId;
			this.prevDescription = this.description;
			this.description = null;
		}

		async onMoveToNextStep(stepId)
		{
			const failedMoveResponse = { finish: false, next: false };
			const successMoveResponse = { finish: false, next: true };
			const finishMoveResponse = { finish: true, next: false };

			if (!this.isConstantsTuned)
			{
				NotifyManager.showError(
					Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_DESCRIPTION_STEP_CONSTANTS_NOT_CONFIGURED'),
				);

				return Promise.resolve(failedMoveResponse);
			}

			if (this.hasFieldsToRender)
			{
				return Promise.resolve(successMoveResponse);
			}

			await NotifyManager.showLoadingIndicator();

			return new Promise((resolve) => {
				BX.ajax.runAction(
					'listsmobile.ElementCreationGuide.createElement',
					{
						data: {
							sign: this.sign,
							fields: { IBLOCK_ID: this.iBlockId },
							timeToStart: this.getCurrentTime() - this.startTime,
						},
					},
				)
					.then(() => {
						NotifyManager.hideLoadingIndicator(true);
						resolve(finishMoveResponse);
					})
					.catch((response) => {
						console.error(response.errors);
						NotifyManager.hideLoadingIndicator(false);

						if (Array.isArray(response.errors))
						{
							NotifyManager.showErrors(response.errors);
						}

						resolve(failedMoveResponse);
					})
				;
			});
		}

		updateProps(props)
		{
			if (this.isLoaded)
			{
				this.onMoveToBackStep();
			}

			this.iBlockId = props.iBlockId === undefined ? this.iBlockId : props.iBlockId;
			this.name = props.name === undefined ? this.name : props.name;
			this.formattedTime = props.formattedTime === undefined ? this.formattedTime : props.formattedTime;
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				isCompleted: this.isLoaded,
				number: this.props.stepNumber,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_DESCRIPTION_STEP_TITLE'),
				},
				number: this.props.stepNumber,
				count: this.hasFieldsToRender ? this.props.totalSteps : this.props.totalSteps - 1,
				previousLineColor: this.isLoaded ? AppTheme.colors.accentMainSuccess : AppTheme.colors.base6,
				currentLineColor: this.isLoaded ? AppTheme.colors.accentExtraAqua : AppTheme.colors.base6,
				nextLineColor: AppTheme.colors.base6,
			};
		}

		getTitle()
		{
			return this.props.title || '';
		}

		getNextStepButtonText()
		{
			if (this.isLoaded && !this.hasFieldsToRender)
			{
				return Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_DESCRIPTION_STEP_NEXT_STEP_IS_FINAL_TITLE');
			}

			return Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_DESCRIPTION_STEP_NEXT_STEP_TITLE');
		}

		isNextStepEnabled()
		{
			return this.isLoaded;
		}

		isPrevStepEnabled()
		{
			return this.isLoaded;
		}

		createLayout(props)
		{
			return new DescriptionStepComponent({
				layout: this.props.layout,
				uid: this.uid,
				iBlockId: this.iBlockId,
				name: this.name,
				formattedTime: this.formattedTime,
				description: this.isPreviousSelectedIBlockId ? this.prevDescription : this.description,
			});
		}
	}

	module.exports = { DescriptionStep };
});
